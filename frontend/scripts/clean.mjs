// 清理脚本：彻底清理所有构建缓存和部署目录
// 解决问题：nuxt generate 不会清理 .nuxt/dist/client 中的旧 chunk,
//          导致 index.html 引用旧入口文件,新代码不生效
// 清理范围：
//   1. .nuxt                  — Nuxt 构建中间产物
//   2. .output                — Nuxt 生成输出
//   3. node_modules/.cache    — 依赖缓存
//   4. node_modules/.vite     — Vite 缓存
//   5. server/public/pc/_nuxt — 部署目录中的 _nuxt 资源(旧 chunk 残留)
//   6. server/public/pc/autodoc — 部署目录中的 autodoc 页面(旧 index.html 残留)
//   7. server/public/pc/*.html — 部署目录中的顶层 HTML(200.html, 404.html, index.html)
import { rmSync, existsSync } from 'node:fs'
import { resolve, dirname } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = dirname(fileURLToPath(import.meta.url))
const webRoot = resolve(__dirname, '..')
const serverPcDir = resolve(webRoot, '../servers/public/pc')
const serverMDir = resolve(webRoot, '../servers/public/m')

const targets = [
  { path: resolve(webRoot, '.nuxt'), label: '.nuxt' },
  { path: resolve(webRoot, '.output'), label: '.output' },
  { path: resolve(webRoot, 'node_modules/.cache'), label: 'node_modules/.cache' },
  { path: resolve(webRoot, 'node_modules/.vite'), label: 'node_modules/.vite' },
  // PC 端部署目录（整体为前端产物，可全清）
  { path: serverPcDir, label: 'servers/public/pc (整体)' },
  // 移动端部署目录（整体为前端产物，可全清）
  { path: serverMDir, label: 'servers/public/m (整体)' },
]

let cleaned = 0
let skipped = 0
for (const t of targets) {
  if (existsSync(t.path)) {
    try {
      rmSync(t.path, { recursive: true, force: true })
      console.log(`[clean] 已清理 ${t.label}`)
      cleaned++
    } catch (err) {
      console.warn(`[clean] 警告: 清理 ${t.label} 失败: ${err.message}`)
    }
  } else {
    skipped++
  }
}
console.log(`[clean] 完成: 清理 ${cleaned} 项, 跳过 ${skipped} 项(不存在)`)
