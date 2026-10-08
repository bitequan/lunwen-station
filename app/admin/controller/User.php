<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\admin\BaseController;
use think\facade\View;
use think\facade\Session;
use think\facade\Db;

class User extends BaseController
{
    /**
     * 用户管理页面
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
        
        // 获取用户列表
        try {
            $list = \app\model\Users::order('id', 'desc')->paginate(20);
            View::assign('list', $list);
        } catch (\Exception $e) {
            // 如果表不存在或查询失败，返回空列表
            View::assign('list', []);
        }
        
        // user/index.html 在 app/admin/view/user/ 目录下
        return View::fetch('user/index');
    }

    /**
     * 用户表单页面
     */
    public function userForm()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return redirect('/admin/login');
        }
        
        // 获取当前登录管理员信息
        $admin = \app\model\Admins::find($adminId);
        View::assign('admin', $admin);
        
        // user/user_form.html 在 app/admin/view/user/ 目录下
        return View::fetch('user/user_form');
    }

    /**
     * 保存用户
     */
    public function saveUser()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 401, 'msg' => '未登录']);
        }
        
        try {
            $data = request()->post();
            
            // 验证必填字段
            if (empty($data['username'])) {
                return json(['code' => 0, 'msg' => '用户名不能为空']);
            }
            
            if (empty($data['password'])) {
                return json(['code' => 0, 'msg' => '密码不能为空']);
            }
            
            // 验证用户名是否已存在
            $existingUser = \app\model\Users::where('username', $data['username'])->find();
            if ($existingUser) {
                return json(['code' => 0, 'msg' => '用户名已存在']);
            }
            
            // 准备用户数据
            $userData = [
                'username' => $data['username'],
                'password' => $data['password'], // 密码修改器会自动哈希
                'nickname' => $data['nickname'] ?? '',
                'email' => $data['email'] ?? '',
                'phone' => $data['phone'] ?? '',
                'status' => isset($data['status']) ? intval($data['status']) : 1,
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s')
            ];
            
            // 保存用户
            $user = new \app\model\Users();
            $result = $user->save($userData);
            
            if ($result) {
                return json(['code' => 1, 'msg' => '用户创建成功']);
            } else {
                return json(['code' => 0, 'msg' => '用户创建失败']);
            }
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '保存失败：' . $e->getMessage()]);
        }
    }

    /**
     * 删除用户
     */
    public function deleteUser()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 401, 'msg' => '未登录']);
        }
        
        try {
            $data = request()->post();
            
            // 验证必填字段
            if (empty($data['id'])) {
                return json(['code' => 0, 'msg' => '用户ID不能为空']);
            }
            
            $userId = intval($data['id']);
            
            // 查找用户
            $user = \app\model\Users::find($userId);
            if (!$user) {
                return json(['code' => 0, 'msg' => '用户不存在']);
            }
            
            // 删除用户
            $result = $user->delete();
            
            if ($result) {
                return json(['code' => 1, 'msg' => '用户删除成功']);
            } else {
                return json(['code' => 0, 'msg' => '用户删除失败']);
            }
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '删除失败：' . $e->getMessage()]);
        }
    }


    /**
     * 更新用户信息
     */
    public function updateUser()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 401, 'msg' => '未登录']);
        }
        
        try {
            $data = request()->post();
            
            // 验证必填字段
            if (empty($data['id'])) {
                return json(['code' => 0, 'msg' => '用户ID不能为空']);
            }
            
            $userId = intval($data['id']);
            
            // 查找用户
            $user = \app\model\Users::find($userId);
            if (!$user) {
                return json(['code' => 0, 'msg' => '用户不存在']);
            }
            
            // 准备更新数据
            $updateData = [
                'nickname' => $data['nickname'] ?? '',
                'email' => $data['email'] ?? '',
                'phone' => $data['phone'] ?? '',
                'status' => isset($data['status']) ? intval($data['status']) : 1,
                'update_time' => date('Y-m-d H:i:s')
            ];
            
            // 如果有新密码，更新密码
            if (!empty($data['new_password'])) {
                $updateData['password'] = $data['new_password']; // 密码修改器会自动哈希
            }
            
            // 更新用户信息
            $result = $user->save($updateData);
            
            if ($result) {
                return json(['code' => 1, 'msg' => '用户信息更新成功']);
            } else {
                return json(['code' => 0, 'msg' => '用户信息更新失败']);
            }
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '更新失败：' . $e->getMessage()]);
        }
    }

    /**
     * 仅更新用户启用/禁用状态（避免通过 updateUser 只传 status 时清空昵称等字段）
     */
    public function setUserStatus()
    {
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 401, 'msg' => '未登录']);
        }

        try {
            $data = request()->post();

            if (empty($data['id'])) {
                return json(['code' => 0, 'msg' => '用户ID不能为空']);
            }

            if (!isset($data['status']) || !in_array((int) $data['status'], [0, 1], true)) {
                return json(['code' => 0, 'msg' => '状态参数无效']);
            }

            $userId = (int) $data['id'];
            $status = (int) $data['status'];

            $user = \app\model\Users::find($userId);
            if (!$user) {
                return json(['code' => 0, 'msg' => '用户不存在']);
            }

            $user->save([
                'status'    => $status,
                'update_time' => date('Y-m-d H:i:s'),
            ]);

            return json([
                'code' => 1,
                'msg'  => $status === 1 ? '已启用该用户' : '已禁用该用户',
            ]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '操作失败：' . $e->getMessage()]);
        }
    }

    /**
     * 管理员手动调整用户余额（增加 / 减少）
     */
    public function userBalance()
    {
        // 检查是否已登录
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            return json(['code' => 401, 'msg' => '未登录']);
        }

        try {
            $data = request()->post();

            if (empty($data['id'])) {
                return json(['code' => 0, 'msg' => '用户ID不能为空']);
            }
            if (empty($data['type']) || !in_array($data['type'], ['inc', 'dec'], true)) {
                return json(['code' => 0, 'msg' => '非法的操作类型']);
            }
            if (!isset($data['amount']) || floatval($data['amount']) <= 0) {
                return json(['code' => 0, 'msg' => '变动金额必须大于0']);
            }

            $userId = intval($data['id']);
            $amount = floatval($data['amount']);
            $remark = trim($data['remark'] ?? '');

            Db::startTrans();

            try {
                // 锁定用户记录
                $user = Db::name('users')
                    ->where('id', $userId)
                    ->lock(true)
                    ->find();

                if (!$user) {
                    Db::rollBack();
                    return json(['code' => 0, 'msg' => '用户不存在']);
                }

                $beforeBalance = floatval($user['balance'] ?? 0);
                $changeAmount = $data['type'] === 'inc' ? $amount : -$amount;
                $afterBalance = $beforeBalance + $changeAmount;

                if ($afterBalance < 0) {
                    Db::rollBack();
                    return json(['code' => 0, 'msg' => '操作后余额不能为负数']);
                }

                // 更新用户余额
                Db::name('users')
                    ->where('id', $userId)
                    ->update([
                        'balance' => $afterBalance,
                        'update_time' => date('Y-m-d H:i:s')
                    ]);

                // 记录余额变动日志（adjust 类型）
                $admin = \app\model\Admins::find($adminId);
                $adminName = $admin ? ($admin['username'] ?? $admin['account'] ?? '') : '';

                if ($remark === '') {
                    $remark = $data['type'] === 'inc' ? '管理员手动增加余额' : '管理员手动减少余额';
                }

                Db::name('balance_logs')->insert([
                    'user_id' => $userId,
                    'change_type' => 'adjust',
                    'change_amount' => $changeAmount,
                    'before_balance' => $beforeBalance,
                    'after_balance' => $afterBalance,
                    'related_id' => '',
                    'related_type' => 'adjust',
                    'remark' => $remark . ($adminName ? "（操作人：{$adminName}）" : ''),
                    'operator_id' => $adminId,
                    'operator_type' => 'admin',
                    'ip_address' => request()->ip(),
                    'create_time' => date('Y-m-d H:i:s'),
                    'bonus_amount' => 0,
                    'total_change_amount' => $changeAmount
                ]);

                Db::commit();


                return json(['code' => 1, 'msg' => '余额调整成功']);
            } catch (\Throwable $e) {
                Db::rollBack();
                return json(['code' => 0, 'msg' => '余额调整失败：' . $e->getMessage()]);
            }
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '请求处理失败：' . $e->getMessage()]);
        }
    }
}
