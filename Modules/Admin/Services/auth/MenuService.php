<?php
/**
 * @Name
 * @Description
 */

namespace Modules\Admin\Services\auth;


use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\AuthGroup as AuthGroupModel;
use Modules\Admin\Models\AuthRule as AuthRuleModel;
use Modules\Admin\Services\BaseApiService;
use Modules\Admin\Services\rule\RuleService;

class MenuService extends BaseApiService
{
    /**
     * @name 获取模块
     * @description
     * @return JSON
     **/
    public function getModel(){
        $userInfo = (new TokenService())->my();
        $AuthRuleModel = AuthRuleModel::query()->where(['type'=>1])->select('id','path','icon','name');
        $data = [];
        if($userInfo['id'] === 1){
            $data = $AuthRuleModel->get()->toArray();
        }else{
            $adminRulesStr = AuthGroupModel::where('id',$userInfo['group_id'])->value('rules');
            if($adminRulesStr){
                $data = $AuthRuleModel->whereIn('id',explode('|',$adminRulesStr))->get()->toArray();
            }
        }
        return $this->apiSuccess('',$data);
    }
    /**
     * @name 获取左侧栏
     * @description
     * @oaram id Int 模块id
     * @return JSON
     **/
    public function getMenu(int $id)
    {
        $data = [];
        $userInfo = (new TokenService())->my();
        if($userInfo['id'] != 1){
            $adminRulesStr = AuthGroupModel::where('id',$userInfo['group_id'])->value('rules');
            if($adminRulesStr){
                $rule = AuthRuleModel::where('type','!=',1)->select('id','pid')->get()->toArray();
                $ruleArr = (new RuleService())->delSort($rule,$id);
                $adminRulesArr = explode('|',$adminRulesStr);
                $rulesArrayIntersect = array_intersect($ruleArr,$adminRulesArr);
                if(count($rulesArrayIntersect)){
//                    $rulesArrayIntersect[] = $id;
                    $data = AuthRuleModel::where('type','!=',1)->whereIn('id',$rulesArrayIntersect)->orderBy('sort', 'asc')->get()->toArray();
                }
            }
        }else{
            $rule = AuthRuleModel::where('type','!=',1)->select('id','pid')->get()->toArray();
//            $rule = DB::table('auth_rules')->where('type','!=',1)->select('id','pid')->get()->toArray();

            $ruleArr = (new RuleService())->delSort($rule,$id);
//            $ruleArr[] = $id;
            $data = AuthRuleModel::where('type','!=',1)->whereIn('id',$ruleArr)->orderBy('sort', 'asc')->get()->toArray();
        }
        return $this->apiSuccess('',$data);
    }
}
