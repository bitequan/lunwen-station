<?php
declare (strict_types = 1);

namespace app\user\controller;

use app\user\BaseController;
use think\facade\Db;
use think\facade\Request;
use think\facade\View;

/**
 * 余额管理控制器
 */
class Balance extends BaseController
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }
    
    /**
     * 余额管理页面
     */
    public function index()
    {
        // 获取当前用户ID
        $userId = $this->getUserId();
        $userBalance = '0.00';
        $updateTime = date('Y-m-d H:i:s');
        
        if ($userId) {
            try {
                // 直接查询数据库获取余额
                $user = Db::name('users')
                    ->where('id', $userId)
                    ->field('balance, update_time')
                    ->find();
                
                if ($user) {
                    $userBalance = $user['balance'] ?? '0.00';
                    $updateTime = $user['update_time'] ?? date('Y-m-d H:i:s');
                    
                    // 记录调试日志
                }
            } catch (\Exception $e) {
            }
        }
        
        // 传递余额数据到视图
        return view('user/balance', [
            'user_balance' => $userBalance,
            'update_time' => $updateTime,
            'user_id' => $userId
        ]);
    }
    
    /**
     * 获取用户余额
     */
    public function getBalance()
    {
        try {
            $userId = $this->getUserId();
            
            // 添加调试日志
            
            if (!$userId) {
                return json(['code' => 0, 'msg' => '请先登录']);
            }
            
            $user = Db::name('users')
                ->where('id', $userId)
                ->field('id, username, balance, update_time')
                ->find();
            
            if (!$user) {
                return json(['code' => 0, 'msg' => '用户不存在']);
            }
            
            
            return json([
                'code' => 1,
                'msg' => '获取成功',
                'data' => [
                    'balance' => $user['balance'] ?? '0.00',
                    'update_time' => $user['update_time'] ?? date('Y-m-d H:i:s'),
                    'debug_user_id' => $userId, // 调试信息
                    'debug_username' => $user['username'] // 调试信息
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取余额失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 创建充值订单
     */
    public function createRechargeOrder()
    {
        try {
            $userId = $this->getUserId();
            if (!$userId) {
                return json(['code' => 0, 'msg' => '请先登录']);
            }
            
            $amount = Request::param('amount', 0);
            $paymentMethod = Request::param('payment_method', '');
            $amount = floatval($amount);
            
            // 验证金额
            if ($amount < 0.01) {
                return json(['code' => 0, 'msg' => '充值金额不能小于0.01元']);
            }
            
            if ($amount > 10000) {
                return json(['code' => 0, 'msg' => '单次充值金额不能超过10000元']);
            }
            
            // 验证支付方式
            if (empty($paymentMethod)) {
                return json(['code' => 0, 'msg' => '请选择支付方式']);
            }
            
            // 获取支付方式信息
            $paymentInfo = $this->getPaymentMethodInfo($paymentMethod);
            if (!$paymentInfo) {
                return json(['code' => 0, 'msg' => '支付方式不存在或未启用']);
            }
            
            // 生成订单号
            $orderNo = $this->generateOrderNo();
            
            // 计算赠送金额
            $bonusResult = \app\model\RechargeBonusRule::calculateBonus($amount);
            $bonusAmount = $bonusResult['bonus_amount'] ?? 0.00;
            $totalAmount = $bonusResult['total_amount'] ?? $amount;
            $bonusRuleId = $bonusResult['rule_id'] ?? null;
            $bonusRuleName = $bonusResult['rule_name'] ?? '';
            
            // 创建充值订单
            $orderData = [
                'order_no' => $orderNo,
                'user_id' => $userId,
                'amount' => $amount,
                'bonus_amount' => $bonusAmount,
                'total_amount' => $totalAmount,
                'bonus_rule_id' => $bonusRuleId,
                'bonus_rule_name' => $bonusRuleName,
                'payment_method' => $paymentMethod,
                'payment_method_name' => $paymentInfo['display_name'] ?? $paymentInfo['name'] ?? '',
                'status' => 0, // 待支付
                'pay_status' => 0, // 未支付
                'ip_address' => Request::ip(),
                'user_agent' => Request::header('User-Agent', ''),
                'expire_time' => date('Y-m-d H:i:s', time() + 1800), // 30分钟后过期
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s')
            ];
            
            $orderId = Db::name('recharge_orders')->insertGetId($orderData);
            
            if (!$orderId) {
                return json(['code' => 0, 'msg' => '创建充值订单失败']);
            }
            
            return json([
                'code' => 1,
                'msg' => '创建成功',
                'data' => [
                    'order_no' => $orderNo,
                    'order_id' => $orderId,
                    'amount' => $amount,
                    'bonus_amount' => $bonusAmount,
                    'total_amount' => $totalAmount,
                    'bonus_rule_id' => $bonusRuleId,
                    'bonus_rule_name' => $bonusRuleName,
                    'bonus_info' => $bonusAmount > 0 ? "赠送 {$bonusAmount} 元" : "无赠送"
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '创建充值订单失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取支付方式信息
     */
    private function getPaymentMethodInfo($paymentCode)
    {
        try {
            // 从支付方式表中获取信息
            $paymentMethod = Db::name('payment_methods')
                ->where('code', $paymentCode)
                ->where('enabled', 1) // 启用状态
                ->field('id, code, name, display_name, icon_text, config')
                ->find();
            
            return $paymentMethod ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * 计算充值赠送金额
     */
    public function calculateBonus()
    {
        try {
            $userId = $this->getUserId();
            if (!$userId) {
                return json(['code' => 0, 'msg' => '请先登录']);
            }
            
            $amount = Request::param('amount', 0);
            $amount = floatval($amount);
            
            // 验证金额
            if ($amount < 0.01) {
                return json(['code' => 0, 'msg' => '充值金额不能小于0.01元']);
            }
            
            // 计算赠送金额
            $bonusResult = \app\model\RechargeBonusRule::calculateBonus($amount);
            
            return json([
                'code' => 1,
                'msg' => '计算成功',
                'data' => [
                    'amount' => $amount,
                    'bonus_amount' => $bonusResult['bonus_amount'],
                    'total_amount' => $bonusResult['total_amount'],
                    'bonus_rule_id' => $bonusResult['rule_id'],
                    'bonus_rule_name' => $bonusResult['rule_name'],
                    'bonus_info' => $bonusResult['bonus_amount'] > 0 ? 
                        "赠送 {$bonusResult['bonus_amount']} 元" : "无赠送"
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '计算失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取交易记录
     */
    public function getHistory()
    {
        try {
            $userId = $this->getUserId();
            if (!$userId) {
                return json(['code' => 0, 'msg' => '请先登录']);
            }
            
            $page = Request::param('page', 1);
            $limit = Request::param('limit', 10);
            $page = max(1, intval($page));
            $limit = max(1, min(100, intval($limit)));
            
            $offset = ($page - 1) * $limit;
            
            // 查询交易记录
            $logs = Db::name('balance_logs')
                ->where('user_id', $userId)
                ->order('create_time', 'desc')
                ->limit($offset, $limit)
                ->select()
                ->toArray();
            
            // 格式化数据
            foreach ($logs as &$log) {
                $log['status'] = 'success'; // 余额日志默认都是成功的
                // 确保金额字段是数字类型
                $log['change_amount'] = floatval($log['change_amount'] ?? 0);
                $log['after_balance'] = floatval($log['after_balance'] ?? 0);
            }
            
            return json([
                'code' => 1,
                'msg' => '获取成功',
                'data' => $logs
            ]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取交易记录失败：' . $e->getMessage()]);
        }
    }

    /**
     * 余额变动日志页面
     */
    public function records()
    {
        try {
            $userId = $this->getUserId();
            if (!$userId) {
                return redirect('/user/index');
            }

            return view('user/balance_logs');
        } catch (\Exception $e) {
            // 出现异常时仍然返回页面，由前端处理错误展示
            return view('user/balance_logs');
        }
    }
    
    /**
     * 生成订单号
     */
    private function generateOrderNo()
    {
        return date('YmdHis') . rand(100000, 999999);
    }
    
    /**
     * 获取用户ID
     */
    private function getUserId()
    {
        // 从session中获取用户ID
        $userId = session('user_id');
        return $userId ? intval($userId) : 0;
    }
    
    /**
     * 获取充值订单信息
     */
    public function getRechargeOrderInfo()
    {
        try {
            $userId = $this->getUserId();
            if (!$userId) {
                return json(['code' => 0, 'msg' => '请先登录']);
            }
            
            $orderNo = Request::param('order_no', '');
            if (empty($orderNo)) {
                return json(['code' => 0, 'msg' => '订单号不能为空']);
            }
            
            $order = Db::name('recharge_orders')
                ->where('order_no', $orderNo)
                ->where('user_id', $userId)
                ->field('order_no, amount, payment_method, payment_method_name, status, pay_status, pay_time, create_time')
                ->find();
            
            if (!$order) {
                return json(['code' => 0, 'msg' => '订单不存在']);
            }
            
            return json([
                'code' => 1,
                'msg' => '获取成功',
                'data' => $order
            ]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取订单信息失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取充值赠送规则列表
     */
    public function getRechargeBonusRules()
    {
        try {
            $userId = $this->getUserId();
            if (!$userId) {
                return json(['code' => 0, 'msg' => '请先登录']);
            }
            
            // 使用模型方法获取所有启用的充值赠送规则
            $rules = \app\model\RechargeBonusRule::getEnabledRules();
            
            // 格式化规则信息
            foreach ($rules as &$rule) {
                // 使用模型方法格式化金额范围显示（左开右闭区间）
                $rule['amount_range_text'] = \app\model\RechargeBonusRule::getAmountRangeText(
                    $rule['min_amount'],
                    $rule['max_amount']
                );
                
                // 使用模型方法格式化赠送信息显示
                $rule['bonus_text'] = \app\model\RechargeBonusRule::getBonusText($rule);
                $rule['bonus_type_text'] = \app\model\RechargeBonusRule::getRuleTypeText($rule['rule_type']);
            }
            
            return json([
                'code' => 1,
                'msg' => '获取成功',
                'data' => $rules
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取充值赠送规则失败：' . $e->getMessage()]);
        }
    }
}

