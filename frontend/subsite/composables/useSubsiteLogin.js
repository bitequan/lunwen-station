// 分站共享：登录弹窗状态管理（供各分站模板复用）
// 用法：const { showLogin, openLogin, closeLogin } = useSubsiteLogin()
// 模板中挂载：<LoginModal v-model="showLogin" @success="onLoginSuccess" />
// 登录成功后由模板自行处理（刷新用户信息、跳转待访问菜单等），保持模板之间互不影响
export function useSubsiteLogin() {
  const showLogin = ref(false)

  function openLogin() {
    showLogin.value = true
  }

  function closeLogin() {
    showLogin.value = false
  }

  return { showLogin, openLogin, closeLogin }
}
