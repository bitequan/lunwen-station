<?php
declare (strict_types = 1);

namespace app\user;

use app\common\utils\AgentIdHelper;
use think\App;

/**
 * user应用控制器基类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {}

    /**
     * 生成对接外部接口使用的 agent_id（可配置前缀 + 用户ID，见 config/docking.php agent_id_prefix）
     *
     * @param int|null $userId
     * @return string|null
     */
    protected function buildAgentId(?int $userId = null): ?string
    {
        if ($userId === null && method_exists($this, 'getUserId')) {
            $userId = (int) $this->getUserId();
        }

        $userId = (int) ($userId ?? 0);
        if ($userId <= 0) {
            return null;
        }

        return AgentIdHelper::build($userId);
    }
}
