<?php
declare(strict_types=1);

namespace app\user\controller;

use think\facade\Db;
use app\common\service\JsonService;

/**
 * 用户端新版公告接口（规划阶段1·公告组）
 *
 * 接口路径：/api/announcement/{action}
 * 统一返回格式：{ code, show, msg, data }（见 JsonService）
 *
 * 说明：oris 库公告表为 ad_announcement（字段 status 表示是否显示）。
 * 对外契约对齐新版主站：
 *   - is_enable   ← status==1
 *   - visibility  ← 2（全站公告，所有访客/用户可见）
 *   - popup_mode  ← 1（登录/挂载时弹窗）
 *   - version     ← update_time 的 Unix 时间戳（强制弹窗轮询依据新公告）
 */
class V2Announcement
{
    /**
     * 弹窗公告（取最新一条启用的，返回版本号供前端去重/轮询）
     */
    public function popup(): \think\response\Json
    {
        $item = Db::name('announcement')
            ->where('status', 1)
            ->order('update_time', 'desc')
            ->find();

        if (!$item) {
            return JsonService::data([
                'has_announcement' => false,
                'version'          => 0,
            ]);
        }

        return JsonService::data([
            'has_announcement' => true,
            'id'               => (int) $item['id'],
            'title'            => (string) ($item['title'] ?? ''),
            'content'          => (string) ($item['content'] ?? ''),
            'version'          => (int) strtotime((string) $item['update_time']),
            'popup_mode'       => 1,
            'visibility'       => 2,
        ]);
    }

    /**
     * 公告记录列表（列表页，不返回 content 大字段）
     */
    public function lists(): \think\response\Json
    {
        $pageNo   = max(1, (int) input('page_no', 1));
        $pageSize = min(50, max(1, (int) input('page_size', 10)));

        $list = Db::name('announcement')
            ->where('status', 1)
            ->field('id,title,create_time,update_time')
            ->order('update_time', 'desc')
            ->page($pageNo, $pageSize)
            ->select()
            ->toArray();

        foreach ($list as &$row) {
            $row['visibility'] = 2;
            $row['popup_mode'] = 1;
        }
        unset($row);

        $count = Db::name('announcement')->where('status', 1)->count();

        return JsonService::data([
            'list'      => $list,
            'count'     => $count,
            'page_no'   => $pageNo,
            'page_size' => $pageSize,
        ]);
    }

    /**
     * 公告详情
     */
    public function detail(): \think\response\Json
    {
        $id = (int) input('id', 0);
        if ($id <= 0) {
            return JsonService::fail('参数错误');
        }
        $item = Db::name('announcement')->where('id', $id)->where('status', 1)->find();
        if (!$item) {
            return JsonService::fail('公告不存在');
        }

        return JsonService::data([
            'id'          => (int) $item['id'],
            'title'       => (string) ($item['title'] ?? ''),
            'content'     => (string) ($item['content'] ?? ''),
            'update_time' => (int) strtotime((string) $item['update_time']),
        ]);
    }
}