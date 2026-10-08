// https://nuxt.com/docs/api/configuration/nuxt-config
import { resolve } from 'node:path'

export default defineNuxtConfig({
  devtools: { enabled: false },
  ssr: false,
  // 运行时配置：API 基址（SSR 阶段需要绝对 URL；客户端走相对路径）
  // 通过环境变量 NUXT_PUBLIC_API_BASE 覆盖；默认空则走站内相对路径（由 Nginx 反代 /api）
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || '',
      // 文件下载网关：默认空（走资源直链）；自建网关时通过环境变量 NUXT_DOWNLOAD_GATEWAY 配置
      downloadGateway: process.env.NUXT_DOWNLOAD_GATEWAY || '',
    },
  },
  app: {
    // baseURL 改为根路径：路由前缀由 pages 目录结构决定（pages/pc/* → /pc/*，pages/m/* → /m/*）
    // 构建资源仍放在 /pc/_nuxt/，保持既有部署目录结构（servers/public/pc/_nuxt）不变
    baseURL: '/',
    buildAssetsDir: '/pc/_nuxt/',
    head: {
      title: 'AI写作助手 - AI论文写作/AI降重/论文查重平台',
      htmlAttrs: { lang: 'zh-CN' },
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        {
          name: 'description',
          content:
            'AI论文写作一键生成、AI率降重降低重复率、论文查重、开题报告、任务书、实习报告一站式搞定。正规高效，欢迎选购。',
        },
        {
          name: 'keywords',
          content:
            'AI论文写作,论文写作平台,AI率降重,论文降重,论文查重,开题报告,任务书,实习报告,AIPPT生成,AI写作助手',
        },
        { 'http-equiv': 'Cache-Control', content: 'no-cache, no-store, must-revalidate' },
        { 'http-equiv': 'Pragma', content: 'no-cache' },
        { 'http-equiv': 'Expires', content: '0' },
      ],
      link: [
        { rel: 'icon', type: 'image/x-icon', href: '/pc/favicon.ico' },
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;600;700;900&display=swap',
        },
      ],
      script: [
        {
          // HTML 不缓存：确保每次拿到最新 index.html，引用最新的 content-hash 资源
          innerHTML: `(function(){ document.documentElement.classList.add('site-page'); })();`,
          tagPosition: 'head',
        },
      ],
    },
  },
  css: ['~/assets/css/style.css', '~/assets/css/m.css'],
  imports: {
    dirs: ['composables'],
  },
  // dev 模式下把 /api/* 代理到 PHP 后端 (生产环境由 Nginx 反代)
  vite: {
    server: {
      proxy: {
        '/api': {
          target: process.env.NUXT_PUBLIC_API_BASE || 'http://127.0.0.1',
          changeOrigin: true,
        },
      },
    },
    build: {
      rollupOptions: {
        input: {
          entry: resolve(process.cwd(), 'node_modules/nuxt/dist/app/entry.js'),
        },
      },
    },
  },
})