<?php
/**
 *  +----------------------------------------------------------------------
 *  | 草帽支付系统 [ WE CAN DO IT JUST THINK ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2018 http://www.iredcap.cn All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed ( https://www.apache.org/licenses/LICENSE-2.0 )
 *  +----------------------------------------------------------------------
 *  | Author: Brian Waring <BrianWaring98@gmail.com>
 *  +----------------------------------------------------------------------
 */

namespace app\common\logic;

use app\common\library\enum\CodeEnum;
use think\Db;
use think\Log;

class Pay extends BaseLogic
{

    /**
     * 获取所有支持的支付方式
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @return mixed
     */
    public function getAppCodeMap(){
        return $this->modelPayCode->getColumn(['status'=>1], 'id,code', $key = 'id');
    }

    /**
     * 获取支付方式列表
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param array $where
     * @param $field
     * @param string $order
     * @return mixed
     */
    public function getCodeList($where = [], $field = true, $order = 'create_time desc',$paginate = 15){
        return $this->modelPayCode->getList($where,$field, $order, $paginate);
    }

    /**
     * 获取支付方式总数
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $where
     * @return mixed
     */
    public function getCodeCount($where = []){
        return $this->modelPayCode->getCount($where);
    }

    /**
     * 获取渠道列表
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param array $where
     * @param $field
     * @param string $order
     * @return mixed
     */
    public function getChannelList($where = [], $field = true, $order = 'create_time desc',$paginate = 15){
        return $this->modelPayChannel->getList($where,$field, $order, $paginate);
    }

    /**
     * 获取渠道总数
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $where
     * @return mixed
     */
    public function getChannelCount($where = []){
        return $this->modelPayChannel->getCount($where);
    }

    /**
     * 获取渠道配置
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $id
     * @return mixed
     */
    public function getChannelParam($id){
        return $this->modelPayChannel->getColumn(['id' => $id], 'id,action,param');
    }

    /**
     * 获取渠道信息
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param array $where
     * @param bool $field
     * @return mixed
     */
    public function getChannelInfo($where = [], $field = true)
    {
        return $this->modelPayChannel->getInfo($where, $field);
    }

    /**
     * 获取支付方式信息
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param array $where
     * @param bool $field
     * @return mixed
     */
    public function getCodeInfo($where = [], $field = true)
    {
        return $this->modelPayCode->getInfo($where, $field);
    }

    /**
     * 添加一个渠道
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $data
     * @return array|string
     */
    public function saveChannelInfo($data){
        //TODO 数据验证
        $validate = $this->validatePayChannel->check($data);

        if (!$validate) {
            return [  'code' => CodeEnum::ERROR,  'msg' => $this->validatePayChannel->getError()];
        }

        //TODO 添加数据
        Db::startTrans();
        try{

            //时间存储
            $data['timeslot'] = json_encode($data['timeslot']);

            $this->modelPayChannel->setInfo($data);

            $action = isset($data['id']) ? '编辑' : '新增';

            action_log($action,  '支付渠道,data:' . http_build_query($data) );

            Db::commit();
            return ['code' =>  CodeEnum::SUCCESS,  'msg' => $action . '渠道成功'];
        }catch (\Exception $ex){
            Db::rollback();
            Log::error($ex->getMessage());
            return [ 'code' => CodeEnum::ERROR,  'msg' => config('app_debug') ? $ex->getMessage() : '未知错误'];
        }

    }

    /**
     * 添加一个方式
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $data
     * @return array|string
     */
    public function saveCodeInfo($data){
        //TODO 数据验证
        $validate = $this->validatePayCode->check($data);

        if (!$validate) {
            return [ 'code' => CodeEnum::ERROR,  'msg' => $this->validatePayCode->getError()];
        }
        //TODO 添加数据
        Db::startTrans();
        try{

            if (isset($data['cnl_id'])) $data['cnl_id'] = arr2str($data['cnl_id'],',');

            $this->modelPayCode->setInfo($data);

            $action = isset($data['id']) ? '编辑' : '新增';

            action_log($action,  '支付方式,data:' . http_build_query($data) );

            Db::commit();
            return [ 'code' => CodeEnum::SUCCESS, 'msg' => $action . '方式成功'];
        }catch (\Exception $ex){
            Db::rollback();
            Log::error($ex->getMessage());
            return [ 'code' => CodeEnum::ERROR,  'msg' => config('app_debug') ? $ex->getMessage() : '未知错误'];
        }

    }


    /**
     * 改变渠道可用性
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $where
     * @param int $value
     * @return array
     */
    public function setChannelStatus($where,$value = 0){
        Db::startTrans();
        try{
            $this->modelPayChannel->setFieldValue($where, $field = 'status', $value);
            Db::commit();
            return [ 'code' => CodeEnum::SUCCESS, 'msg' => '修改状态成功'];
        }catch (\Exception $ex){
            Db::rollback();
            Log::error($ex->getMessage());
            return [ 'code' => CodeEnum::ERROR,  'msg' => config('app_debug') ? $ex->getMessage() : '未知错误'];
        }
    }

    /**
     * 删除一个方式
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $where
     * @return array
     */
    public function delCode($where){
        Db::startTrans();
        try{
            $this->modelPayCode->deleteInfo($where);
            action_log('删除', '删除支付方式，ID：'. $where['id']);
            Db::commit();
            return [ 'code' => CodeEnum::SUCCESS, 'msg' => '删除方式成功'];
        }catch (\Exception $ex){
            Db::rollback();
            Log::error($ex->getMessage());
            return [ 'code' => CodeEnum::ERROR,  'msg' => config('app_debug') ? $ex->getMessage() : '未知错误'];
        }
    }

    /**
     * 删除一个渠道
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $where
     * @return array
     */
    public function delChannel($where){
        Db::startTrans();
        try{
            $this->modelPayChannel->deleteInfo($where);
            action_log('删除', '删除支付渠道，ID：'. $where['id']);
            Db::commit();
            return [ 'code' => CodeEnum::SUCCESS, 'msg' => '删除渠道成功'];
        }catch (\Exception $ex){
            Db::rollback();
            Log::error($ex->getMessage());
            return [ 'code' => CodeEnum::ERROR,  'msg' => config('app_debug') ? $ex->getMessage() : '未知错误'];
        }
    }
}