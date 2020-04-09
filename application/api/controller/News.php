<?php

namespace app\api\controller;
use app\common\assist\NewsAssist;
use app\common\constant\ContentConstant;
use app\common\constant\BannerConstant;
use app\common\constant\SystemConstant;

class News extends Base implements NewsAssist
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 新闻列表
     * http://localhost/News/newsList
     */
    public function newsList()
    {
        if (request()->isPost()) {
            $news_model = model('news');
            $list_row = input('post.list_row', 10); //每页数据
            $page = input('post.page', 1); //当前页

            $where = ['is_display' => 1];
            $totalCount = $news_model->where($where)->count();
            $first_row = ($page-1)*$list_row;
            $field = ['id','title','news_pic','des', 'add_time'];
            $lists = $news_model->where($where)->field($field)->limit($first_row, $list_row)->order('add_time desc, sort asc, id desc')->select();

            $pageCount = ceil($totalCount/$list_row);

            $data = [
                'list' => $lists ? $lists : [],
                'totalCount' => $totalCount ? $totalCount : 0,
                'pageCount' => $pageCount ? $pageCount : 0,
            ];

            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            exit(json_encode($json_arr));
        }
    }

    /**
     * 新闻详情
     * http://localhost/News/newsInfo
     */
    public function newsDetail()
    {
        if (request()->isPost()) {
            $id = request()->post('id');
            if (!$id) {
                $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []];
                echo json_encode($json_arr);die;
            }
            $news_model = model('news');
            $news = $news_model->field('title,author,add_time,content')->where(['id' => $id])->find();
            $news['content'] = stripslashes(htmlspecialchars_decode($news['content']));
            $news['content'] = str_replace('src="/uploads', 'src="'.getSetting('system.host').'/uploads' , $news['content']);
            $news['content'] = str_replace('src="/Uploads', 'src="'.getSetting('system.host').'/uploads' , $news['content']);

            $news_list =  $news_model->where(['is_display' => 1])->field('id,title')->order('add_time desc')->select();
            $prev_id = 0;
            $prev_title = '没有了';
            $next_id = 0;
            $next_title = '没有了';
            foreach ($news_list as $k => $v) {
                if ($id == $v['id']) {
                    if ($k == 0) {
                        $prev_id = 0;
                        $prev_title = '没有了';
                    } else {
                        $prev_id = $news_list[$k-1]['id'];
                        $prev_title = $news_list[$k-1]['title'];
                    }
                    if ($k == count($news_list)-1) {
                        $next_id = 0;
                        $next_title = '没有了';
                    } else {
                        $next_id = $news_list[$k+1]['id'];
                        $next_title = $news_list[$k+1]['title'];
                    }
                }
            }
           /* $prev_id_first = $news_model->where(['is_display' => 1])->order('add_time desc, sort asc, id desc')->value('add_time');
            //echo json_encode($prev_id_first) ;
            if ($prev_id_first == $news['add_time']) {
                $prev_id = 0;
                $prev_title = '没有了';
            } else {
                $prev_id = $news_model->where(['is_display' => 1, 'add_time' => ['gt',  $news['add_time']]])->order('add_time desc, sort desc, id asc')->value('id');
                $prev_title = $news_model->where(['id' => $prev_id])->value('title');
            }

            $next_id_last = $news_model->where(['is_display' => 1])->order('add_time asc, sort desc, id asc')->value('add_time');
            if ($next_id_last == $news['add_time']) {
                $next_id = 0;
                $next_title = '没有了';
            } else {
                $next_id = $news_model->where(['is_display' => 1, 'add_time' => ['lt', $news['add_time']]])->order('add_time desc, sort asc, id desc')->value('id');
                $next_title = $news_model->where(['id' => $next_id])->value('title');
            }*/

            $data = [
                'news' => $news,
                'prev_next' => [
                    'prev_id' => $prev_id,
                    'prev_title' => $prev_title,
                    'next_id' => $next_id,
                    'next_title' => $next_title,
                ],
            ];
            $json_arr =  ["status"=>1, "msg"=>SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            echo json_encode($json_arr) ;
        }
    }
}