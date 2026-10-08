<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\admin\BaseController;
use think\facade\View;
use think\facade\Session;
use think\facade\Request;
use app\model\RechargeBonusRule;

class RechargeBonus extends BaseController
{
    /**
     * 充值赠送规则管理页面
     */
    public function index()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return redirect('/admin/login');
        }
        
        // 获取当前登录管理员信息
        $admin = \app\model\Admins::find($adminId);
        View::assign('admin', $admin);
        
        return View::fetch('recharge_bonus/index');
    }
    
    /**
     * 获取充值赠送规则列表
     */
    public function getRules()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        try {
            // 获取所有规则
            $rules = RechargeBonusRule::order('sort_order', 'asc')
                ->order('min_amount', 'asc')
                ->select()
                ->toArray();
            
            // 格式化数据
            $formattedRules = [];
            foreach ($rules as $rule) {
                $formattedRules[] = [
                    'id' => $rule['id'],
                    'name' => $rule['name'],
                    'rule_type' => $rule['rule_type'],
                    'rule_type_text' => RechargeBonusRule::getRuleTypeText($rule['rule_type']),
                    'min_amount' => $rule['min_amount'],
                    'max_amount' => $rule['max_amount'],
                    'amount_range_text' => RechargeBonusRule::getAmountRangeText($rule['min_amount'], $rule['max_amount']),
                    'bonus_percent' => $rule['bonus_percent'],
                    'bonus_fixed' => $rule['bonus_fixed'],
                    'bonus_text' => RechargeBonusRule::getBonusText($rule),
                    'description' => $rule['description'],
                    'sort_order' => $rule['sort_order'],
                    'status' => $rule['status'],
                    'status_text' => RechargeBonusRule::getStatusText($rule['status']),
                    'create_time' => $rule['create_time'],
                    'update_time' => $rule['update_time']
                ];
            }
            
            return json(['code' => 1, 'data' => $formattedRules]);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取规则列表失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取单个规则
     */
    public function getRule()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $id = Request::get('id', 0);
        if (!$id) {
            return json(['code' => 0, 'msg' => '规则ID不能为空']);
        }
        
        try {
            $rule = RechargeBonusRule::find($id);
            if (!$rule) {
                return json(['code' => 0, 'msg' => '规则不存在']);
            }
            
            return json(['code' => 1, 'data' => $rule->toArray()]);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取规则失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 保存规则
     */
    public function saveRule()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $data = Request::post();
        
        try {
            // 验证数据
            $errors = RechargeBonusRule::validateRuleData($data);
            if (!empty($errors)) {
                return json(['code' => 0, 'msg' => implode('；', $errors)]);
            }
            
            // 处理空值
            if (isset($data['max_amount']) && ($data['max_amount'] === '' || $data['max_amount'] === null)) {
                $data['max_amount'] = null;
            }
            
            // 检查金额范围是否重叠
            $excludeId = isset($data['id']) && $data['id'] ? (int)$data['id'] : null;
            if (RechargeBonusRule::checkAmountRangeOverlap($data, $excludeId)) {
                return json(['code' => 0, 'msg' => '金额范围与其他规则重叠，请调整']);
            }
            
            // 保存数据
            if (!empty($data['id'])) {
                // 更新
                $rule = RechargeBonusRule::find($data['id']);
                if (!$rule) {
                    return json(['code' => 0, 'msg' => '规则不存在']);
                }
                $rule->save($data);
            } else {
                // 新增
                $rule = new RechargeBonusRule();
                $rule->save($data);
            }
            
            return json(['code' => 1, 'msg' => '保存成功', 'data' => ['id' => $rule->id]]);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '保存失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 更新规则状态
     */
    public function updateRuleStatus()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $data = Request::post();
        
        try {
            // 验证必填字段
            if (empty($data['id']) || !isset($data['status'])) {
                return json(['code' => 0, 'msg' => '规则ID和状态不能为空']);
            }
            
            $rule = RechargeBonusRule::find($data['id']);
            if (!$rule) {
                return json(['code' => 0, 'msg' => '规则不存在']);
            }
            
            $rule->status = $data['status'] ? 1 : 0;
            $rule->save();
            
            return json(['code' => 1, 'msg' => '状态更新成功']);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '更新状态失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 更新规则排序
     */
    public function updateRuleSort()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $data = Request::post();
        
        try {
            // 验证必填字段
            if (empty($data['id']) || !isset($data['sort_order'])) {
                return json(['code' => 0, 'msg' => '规则ID和排序值不能为空']);
            }
            
            $rule = RechargeBonusRule::find($data['id']);
            if (!$rule) {
                return json(['code' => 0, 'msg' => '规则不存在']);
            }
            
            $rule->sort_order = (int)$data['sort_order'];
            $rule->save();
            
            return json(['code' => 1, 'msg' => '排序更新成功']);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '更新排序失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 删除规则
     */
    public function deleteRule()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $id = Request::post('id', 0);
        if (!$id) {
            return json(['code' => 0, 'msg' => '规则ID不能为空']);
        }
        
        try {
            $rule = RechargeBonusRule::find($id);
            if (!$rule) {
                return json(['code' => 0, 'msg' => '规则不存在']);
            }
            
            // 检查是否有充值订单使用了此规则
            $usedCount = \app\model\RechargeOrders::where('bonus_rule_id', $id)->count();
            if ($usedCount > 0) {
                return json(['code' => 0, 'msg' => '该规则已被使用，无法删除']);
            }
            
            $rule->delete();
            
            return json(['code' => 1, 'msg' => '删除成功']);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '删除失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 测试计算赠送金额
     */
    public function testCalculate()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $amount = Request::post('amount', 0);
        if ($amount <= 0) {
            return json(['code' => 0, 'msg' => '请输入有效的充值金额']);
        }
        
        try {
            $result = RechargeBonusRule::calculateBonus((float)$amount);
            
            return json([
                'code' => 1,
                'data' => $result,
                'msg' => '计算成功'
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '计算失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 检查金额范围是否重叠
     */
    public function checkAmountRangeOverlap()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 0, 'msg' => '未登录']);
        }
        
        $data = Request::post();
        
        try {
            // 验证数据
            $errors = RechargeBonusRule::validateRuleData($data);
            if (!empty($errors)) {
                return json(['code' => 0, 'msg' => implode('；', $errors)]);
            }
            
            // 处理空值
            if (isset($data['max_amount']) && ($data['max_amount'] === '' || $data['max_amount'] === null)) {
                $data['max_amount'] = null;
            }
            
            // 检查金额范围是否重叠
            $excludeId = isset($data['id']) && $data['id'] ? (int)$data['id'] : null;
            $overlap = RechargeBonusRule::checkAmountRangeOverlap($data, $excludeId);
            
            if ($overlap) {
                // 获取重叠的规则信息
                $overlappingRules = $this->getOverlappingRulesInfo($data, $excludeId);
                $message = '金额范围与以下规则重叠：' . implode('、', $overlappingRules);
                
                return json([
                    'code' => 1,
                    'data' => [
                        'overlap' => true,
                        'message' => $message
                    ],
                    'msg' => '检查完成'
                ]);
            } else {
                return json([
                    'code' => 1,
                    'data' => [
                        'overlap' => false,
                        'message' => '金额范围无重叠'
                    ],
                    'msg' => '检查完成'
                ]);
            }
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '检查失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取重叠的规则信息
     * @param array $data 当前规则数据
     * @param int|null $excludeId 排除的规则ID
     * @return array 重叠规则的信息列表
     */
    private function getOverlappingRulesInfo(array $data, ?int $excludeId = null): array
    {
        $minAmount = $data['min_amount'];
        $maxAmount = $data['max_amount'] ?? null;
        
        $query = RechargeBonusRule::where('status', RechargeBonusRule::STATUS_ENABLED);
        
        if ($excludeId) {
            $query->where('id', '<>', $excludeId);
        }
        
        $rules = $query->select();
        $overlappingRules = [];
        
        foreach ($rules as $rule) {
            $ruleMin = $rule['min_amount'];
            $ruleMax = $rule['max_amount'];
            
            // 检查范围是否重叠
            $isOverlap = false;
            
            if ($maxAmount === null && $ruleMax === null) {
                // 两个规则都无上限
                $isOverlap = ($minAmount == $ruleMin);
            } elseif ($maxAmount === null) {
                // 当前规则无上限
                $isOverlap = ($minAmount < $ruleMax);
            } elseif ($ruleMax === null) {
                // 其他规则无上限
                $isOverlap = ($ruleMin < $maxAmount);
            } else {
                // 两个规则都有上限
                $isOverlap = ($minAmount < $ruleMax && $ruleMin < $maxAmount);
            }
            
            if ($isOverlap) {
                $rangeText = RechargeBonusRule::getAmountRangeText($ruleMin, $ruleMax);
                $overlappingRules[] = $rule['name'] . ' (' . $rangeText . ')';
            }
        }
        
        return $overlappingRules;
    }
}
