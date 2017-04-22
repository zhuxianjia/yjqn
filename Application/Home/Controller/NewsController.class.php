<?php
namespace Home\Controller;
use Think\Controller;
class NewsController extends Controller {

public $url='https://yjh-server.780.cn';
public $link='https://yjh-portal.780.cn';
    private function get_access_token(){
        $post_data=array('grant_type'=>"client_credential",'app_id'=>20405,"app_secret"=>"8742927afe4c3e863693503ffdc9c0d4");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url."/openapi/oauth2/app_token");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, $post_data);
        $code=curl_exec($ch);
        curl_close($ch); 
        $object=json_decode($code);
        $ret=$object->ret;
        $data=$object->data;
        if($ret==0){
            $user_data['access_token']=$data->access_token;
            $user_data['expires_in']=$data->expires_in;
        }
        return $user_data;
    }

    public function httpPost($post_data,$url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, $post_data);
        $code=curl_exec($ch);
        curl_close($ch); 
        return $code;
    }

    private function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
          $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public function push_msg_to_users(){
        $article_id=I('post.article_id');//article_id 文章id
        $list=explode(',',I('post.list'));
        $inner=I('post.data');//data  识别字段
        // $article_id=4185;
        // $list=array(20980,20981);
        // $inner='inner';
        if($inner=='inner'){
            ignore_user_abort();//关闭浏览器后，继续执行php代码
            set_time_limit(0);//程序执行时间无限制
            $num=100;//每次传输长度
            $token=$this->get_access_token();
            $access_token=$token['access_token'];
            if(!$access_token) $arr=array('state'=>'10056','detail'=>'秘钥获取失败！');
            else{
                $length=count($list);
                for($i=0;$i<$length;$i++){
                    $noncestr=$this->createNonceStr(16);
                    $push=array_slice($list,$i,$num);
                    if($push){
                        $info=M('article')->where('id=%d',$article_id)->find();
                        $conditiono['id']=array('in',$push);
                        $open_ids=M('user')->where($conditiono)->getField('guid',true);
                        $i+=$num-1;
                        $info['text']=strip_tags(html_entity_decode($info['text']));
                        if(mb_strwidth($info['text'], 'utf8')>200){
                            $info['text'] = mb_strimwidth($info['text'], 0, 200, '...', 'utf8');
                            $info['text']=str_replace("\n","",$info['text']);
                            $info['text']=str_replace(" ","",$info['text']);
                        }
                        if(!$info['link']) $info['link']=$this->link.'/Index/pages/newsDetail.html?id='.$article_id.',function='.$info['function'];
                        if(!$info['text']) $info['text']='新推送';
                        $post_data=array('access_token'=>$access_token,'app_id'=>20405,'msg_id'=>$noncestr,'open_ids'=>implode(',',$open_ids),'title'=>$info['title'],'content'=>$info['text'],'url'=>$info['link']);
                        if($info['function']==2) $post_data['image_url']=$this->link.M('photo')->where('article_id=%d',$article_id)->getField('compress_photo');
                        else if($info['compress_photo']) $post_data['image_url']=$this->link.$info['compress_photo'];
                        if($post_data['image_url']){
                            $imageInfo=getimagesize($post_data['image_url']);
                            $post_data['image_align']='top';
                            $post_data['image_width']=$imageInfo[0];
                            $post_data['image_height']=$imageInfo[1];
                        }
                        $url=$this->url.'/openapi/app/push_msg_to_users';
                        $ret=$this->httpPost($post_data,$url);
                        $data=array();
                        foreach ($push as $value) {
                            $data[]=array('article_id'=>$article_id,'user_id'=>$value,'time'=>time(),'noncestr'=>$noncestr);
                        }
                        $result=M('article_push')->addAll($data);
                        
                        $conditioni['id']=$article_id;
                        if(M('article')->where($conditioni)->find()){
                            $condition['article_id']=$article_id;
                            $ispushcount=count(M('article_push')->where($condition)->getField('id',true));
                            $article_data['schedule']=round($ispushcount/$length*100);
                            $result=$result&&M('article')->where($conditioni)->save($article_data);
                        }
                        if($ret==0&&$result) $arr=array('state'=>'0','detail'=>'推送成功！');
                        else $arr=array('state'=>'10001','detail'=>'系统异常！');
                    }
                    else $arr=array('state'=>'0','detail'=>'推送成功！');
                }
            }
        }
        else  $arr=array('state'=>'10003','detail'=>'参数错误！');
        $this->ajaxreturn($arr);
    }

    public function GetArticleSchedule(){
        $article_id=I('id');
        $user_id=session('adminuser_id');
        if(!$user_id) $arr=array('state'=>'10000','detail'=>'未登录！');
        else if(!D('backstage')->check_article($article_id)) $arr=array('state'=>'10003','detail'=>'参数错误！');
        else{
            $data=M('article')->where('id=%d',$article_id)->find();
            $arr=array('state'=>'0','order'=>$data['schedule']);
            // if($data['schedule']==100){
            //     $article_data['schedule']=0;
            //     M('article')->where('id=%d',$article_id)->save($article_data);//进度到100后即修改为0，为下次推送做准备
            // }
        }
        $this->ajaxreturn($arr);
    }

    public function articleUpdate(){
        $inner=I('post.data');//data  识别字段
        if($inner=='inner'){
            ignore_user_abort();//关闭浏览器后，继续执行php代码
            set_time_limit(0);//程序执行时间无限制
            $data=M('article')->where('type=1')->order('in_time')->select();
            $length=count($data);
            if($length>5){
                for($i=0;$i<count($data);$i++){
                    $save['type']=0;
                    M('article')->where('id=%d',$data[$i]['id'])->save($save);
                    $rest=count(M('article')->where('type=1')->select());
                    if($rest==5){
                        break;
                    }
                }            
            }
        }
        else  $arr=array('state'=>'10003','detail'=>'参数错误！');
        $this->ajaxreturn($arr);
    }




}