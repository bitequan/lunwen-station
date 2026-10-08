// 部署脚本：将 .output/public 部署到 server/public/pc 与 server/public/m
// 目录结构（baseURL=/）：
//   .output/public/pc/*   → servers/public/pc/   （PC 端页面 + _nuxt 资源）
//   .output/public/m/*    → servers/public/m/    （移动端页面）
//   .output/public 顶层文件（200.html/404.html/sitemap.xml 等）→ 同时落到 pc/ 与 m/（SPA fallback 与静态资源）
//   站点根目录不写入任何文件（根 / 行为保持现状，由 nginx 决定）
// 解决问题：nuxt build 在 SPA 模式下不生成 index.html；旧构建残留导致页面无变化
//   + 强化：给每个 HTML 注入 <meta http-equiv="Cache-Control"> 等，兼容未读取 .htaccess 的 Nginx/宝塔
//   + 强化：前端 runtime 版本自检，发现旧构建立即 reload(true) 强制绕缓存 + __v 追加兜底
import { rm, cp, mkdir, readFile, writeFile, readdir, unlink } from 'node:fs/promises'
import { existsSync } from 'node:fs'
import { resolve, dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = dirname(fileURLToPath(import.meta.url))
const webRoot = resolve(__dirname, '..')
const outputDir = resolve(webRoot, '.output/public')
const outputPcDir = join(outputDir, 'pc')
const outputMDir = join(outputDir, 'm')
// 兼容两种目录布局：
//   开发仓库：source/ 与 servers/ 平级 → 部署到 ../servers/public/{pc,m}
//   开源发布包：source/ 与 public/ 平级 → 部署到 ../public/{pc,m}
let targetPcDir = resolve(webRoot, '../servers/public/pc')
let targetMDir = resolve(webRoot, '../servers/public/m')
if (!existsSync(resolve(webRoot, '../servers'))) {
  targetPcDir = resolve(webRoot, '../public/pc')
  targetMDir = resolve(webRoot, '../public/m')
}

/**
 * 给单个 HTML 文件内容注入 meta no-cache 标签（兼容 Nginx/宝塔未读 .htaccess 的情况）
 * 同时把 window.__NUXT__.config 里的 buildId 同步写入，避免 latest.json 指向不一致
 * 再加一段前端 runtime 版本自检：如果用户本地 localStorage 里存的 buildId 与当前 HTML 的 buildId
 * 不一致，就用 location.reload(true) 强制绕过 HTTP 缓存刷新一次，彻底解决"明明 build 了用户电脑仍读旧 HTML"。
 */
function injectHtmlCacheControl(html, buildId) {
  const noCacheMeta = [
    '<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">',
    '<meta http-equiv="Pragma" content="no-cache">',
    '<meta http-equiv="Expires" content="0">',
  ].join('')
  // 版本自检脚本（放在 <head> 最前，尽早执行 —— 如果发现是旧构建，立即强刷）
  const versionSelfCheck = buildId ? `
<script data-build-version-self-check>
(function () {
  try {
    var CUR = ${JSON.stringify(buildId)};
    var KEY = '__pc_build_id_v1';
    var prev = null;
    try { prev = localStorage.getItem(KEY); } catch (e) { prev = null; }
    // 若没有旧记录 → 记录并放行
    if (!prev) {
      try { localStorage.setItem(KEY, CUR); } catch (_) {}
      return;
    }
    // 记录不一致 → 这是"HTML 被浏览器 HTTP 缓存命中导致读到旧版本"的经典症状，
    // 强刷一次强制回源（true 参数会绕过 Cache-Control）
    if (prev !== CUR) {
      try { localStorage.setItem(KEY, CUR); } catch (_) {}
      // 用 reload(true) 绕过 HTTP 层缓存，避免再命中旧 HTML
      if (typeof window.location.reload === 'function') {
        setTimeout(function () {
          try { window.location.reload(true); } catch (_) {
            // 万一 reload(true) 不生效，就追加 ?__v=<buildId> 兜底
            window.location.href = window.location.href + (window.location.search ? '&' : '?') + '__v=' + encodeURIComponent(CUR);
          }
        }, 0);
      }
    }
  } catch (e) { /* 任何错误都不阻塞页面 */ }
})();
</script>` : ''
  // 先替换 </head> 注入 no-cache meta + 版本自检
  let result = html.replace(/<\/head>/i, `${noCacheMeta}${versionSelfCheck}</head>`)
  // 如果有 buildId，也同步覆盖脚本里的 buildId，双保险
  if (buildId) {
    result = result.replace(/buildId:\s*"[^"]*"/g, `buildId:"${buildId}"`)
  }
  return result
}

/** 递归遍历 dir，给所有 .html/.htm 文件注入 no-cache meta */
async function injectHtmlDir(dir, buildId) {
  const entries = await readdir(dir, { withFileTypes: true })
  for (const entry of entries) {
    const full = join(dir, entry.name)
    if (entry.isDirectory()) {
      await injectHtmlDir(full, buildId)
    } else if (/\.(html?)$/i.test(entry.name)) {
      const raw = await readFile(full, 'utf-8')
      const updated = injectHtmlCacheControl(raw, buildId)
      if (updated !== raw) {
        await writeFile(full, updated, 'utf-8')
      }
    }
  }
}

/** 复制目录下所有顶层「文件」（不含子目录）到目标目录 */
async function copyTopLevelFiles(srcDir, destDir, { exclude = [] } = {}) {
  if (!existsSync(srcDir)) return
  const entries = await readdir(srcDir, { withFileTypes: true })
  for (const entry of entries) {
    if (!entry.isFile()) continue
    if (exclude.includes(entry.name)) continue
    await cp(join(srcDir, entry.name), join(destDir, entry.name))
  }
}

const HTACCESS_CONTENT = `# ===== 缓存策略（自动生成，请勿手动修改）=====
# HTML 不缓存：确保浏览器每次拿到最新 index.html
<IfModule mod_headers.c>
  <FilesMatch "\\.(html|htm)$">
    Header set Cache-Control "no-cache, no-store, must-revalidate"
    Header set Pragma "no-cache"
    Header set Expires "0"
  </FilesMatch>
  # _nuxt 资源长缓存（content hash 保证文件名变化即失效）
  <FilesMatch "\\.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$">
    Header set Cache-Control "public, max-age=31536000, immutable"
  </FilesMatch>
</IfModule>

<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType text/html "access plus 0 seconds"
  ExpiresByType text/css "access plus 1 year"
  ExpiresByType application/javascript "access plus 1 year"
  ExpiresByType text/javascript "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/svg+xml "access plus 1 year"
  ExpiresByType font/woff2 "access plus 1 year"
</IfModule>
`

async function deployOne(label, sourceSubDir, targetDir, buildId) {
  console.log(`[deploy] 清理旧部署目录 ${label} ...`)
  if (existsSync(targetDir)) {
    await rm(targetDir, { recursive: true, force: true })
  }
  await mkdir(targetDir, { recursive: true })

  console.log(`[deploy] 复制 .output/public/${sourceSubDir} -> ${label} ...`)
  await cp(join(outputDir, sourceSubDir), targetDir, { recursive: true })

  // 顶层静态文件（200/404 fallback、sitemap、robots、favicon 等）落到端目录
  // 注意排除 index.html：端目录自己的 index.html 才是该端根路由（/pc 或 /m）的正确入口
  await copyTopLevelFiles(outputDir, targetDir, { exclude: ['index.html'] })

  // 修复 latest.json 指向当前 buildId
  const latestJsonPath = resolve(targetDir, '_nuxt/builds/latest.json')
  if (existsSync(latestJsonPath)) {
    const latest = JSON.parse(await readFile(latestJsonPath, 'utf-8'))
    if (latest.id !== buildId) {
      latest.id = buildId
      latest.timestamp = Date.now()
      await writeFile(latestJsonPath, JSON.stringify(latest))
      console.log(`[deploy] 修复 latest.json -> ${buildId}`)
    }
  }

  // 清理 _nuxt/builds/meta/ 下非当前 buildId 的旧元数据
  const metaDir = resolve(targetDir, '_nuxt/builds/meta')
  if (existsSync(metaDir)) {
    const files = await readdir(metaDir)
    for (const f of files) {
      if (!f.startsWith(buildId)) {
        await unlink(resolve(metaDir, f))
        console.log(`[deploy] 清理旧 build meta: ${f}`)
      }
    }
  }

  await writeFile(resolve(targetDir, '.htaccess'), HTACCESS_CONTENT)
}

async function main() {
  // 1. 检查输出目录是否存在
  if (!existsSync(outputPcDir) || !existsSync(outputMDir)) {
    console.error('[deploy] 错误: 未找到 .output/public/pc 或 .output/public/m，请先运行 nuxt generate')
    process.exit(1)
  }

  // 2. 从 pc/index.html 提取当前 buildId
  const indexHtmlPath = resolve(outputPcDir, 'index.html')
  const indexHtml = await readFile(indexHtmlPath, 'utf-8')
  const match = indexHtml.match(/buildId:"([a-f0-9-]+)"/)
  const buildId = match ? match[1] : null
  if (!buildId) {
    console.error('[deploy] 错误: 无法从 index.html 提取 buildId')
    process.exit(1)
  }
  console.log(`[deploy] 当前 buildId: ${buildId}`)

  // 3. 分别部署 PC 端与移动端
  await deployOne('servers/public/pc', 'pc', targetPcDir, buildId)
  await deployOne('servers/public/m', 'm', targetMDir, buildId)

  // 4. 给部署目录下所有 HTML 注入 no-cache meta + 同步 buildId（兼容 Nginx/宝塔不读 .htaccess）
  await injectHtmlDir(targetPcDir, buildId)
  await injectHtmlDir(targetMDir, buildId)
  console.log('[deploy] 已注入所有 HTML no-cache meta 标签 + buildId 版本自检脚本')
  console.log('[deploy] 已写入 .htaccess 缓存策略')

  console.log('')
  console.log('[deploy] 部署完成 ✓')
  console.log(`[deploy] PC 目标: ${targetPcDir}`)
  console.log(`[deploy] H5 目标: ${targetMDir}`)
  console.log('[deploy] 访问: {site_domain}/pc/ 与 {site_domain}/m/')
}

main().catch(err => {
  console.error('[deploy] 失败:', err)
  process.exit(1)
})
