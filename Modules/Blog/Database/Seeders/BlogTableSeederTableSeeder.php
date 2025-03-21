<?php

namespace Modules\Blog\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class BlogTableSeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /****************************文章分类添加******************************************/
        $pid1 = DB::table('blog_article_types')->insertGetId([
            'name'=>'前端技术',
            'pid'=>0,
            'level'=>1,
			'sort'=>1,
            'project_id'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        DB::table('blog_article_types')->insert([
            'name'=>'HTML',
            'pid'=>$pid1,
            'level'=>2,
            'sort'=>1,
            'project_id'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        DB::table('blog_article_types')->insert([
            'name'=>'CSS',
            'pid'=>$pid1,
            'level'=>2,
            'sort'=>2,
            'project_id'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        DB::table('blog_article_types')->insert([
            'name'=>'JavaScript',
            'pid'=>$pid1,
            'level'=>2,
            'sort'=>3,
            'project_id'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        DB::table('blog_article_types')->insert([
            'name'=>'TypeScript',
            'pid'=>$pid1,
            'level'=>2,
            'sort'=>4,
            'project_id'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        $pid1 = DB::table('blog_article_types')->insertGetId([
            'name'=>'后端技术',
            'pid'=>0,
            'level'=>1,
            'sort'=>2,
            'project_id'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        DB::table('blog_article_types')->insert([
            'name'=>'Java',
            'pid'=>$pid1,
            'level'=>2,
            'sort'=>1,
            'project_id'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        DB::table('blog_article_types')->insert([
            'name'=>'PHP',
            'pid'=>$pid1,
            'level'=>2,
            'sort'=>2,
            'project_id'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        DB::table('blog_article_types')->insert([
            'name'=>'Python',
            'pid'=>$pid1,
            'level'=>2,
            'sort'=>3,
            'project_id'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        DB::table('blog_article_types')->insert([
            'name'=>'C#',
            'pid'=>$pid1,
            'level'=>2,
            'sort'=>4,
            'project_id'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        DB::table('blog_article_types')->insert([
            'name'=>'Go',
            'pid'=>$pid1,
            'level'=>2,
            'sort'=>5,
            'project_id'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        DB::table('blog_article_types')->insert([
            'name'=>'Objective-C',
            'pid'=>$pid1,
            'level'=>2,
            'sort'=>6,
            'project_id'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        DB::table('blog_article_types')->insert([
            'name'=>'C',
            'pid'=>$pid1,
            'level'=>2,
            'sort'=>7,
            'project_id'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        DB::table('blog_article_types')->insert([
            'name'=>'C++',
            'pid'=>$pid1,
            'level'=>2,
            'sort'=>8,
            'project_id'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);


        $pid1 = DB::table('blog_article_types')->insertGetId([
            'name'=>'数据库',
            'pid'=>0,
            'level'=>1,
            'sort'=>2,
            'project_id'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        DB::table('blog_article_types')->insert([
            'name'=>'Oracle',
            'pid'=>$pid1,
            'level'=>2,
            'sort'=>1,
            'project_id'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        DB::table('blog_article_types')->insert([
            'name'=>'MySQL',
            'pid'=>$pid1,
            'level'=>2,
            'sort'=>2,
            'project_id'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        DB::table('blog_article_types')->insert([
            'name'=>'MariaDB',
            'pid'=>$pid1,
            'level'=>2,
            'sort'=>3,
            'project_id'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        DB::table('blog_article_types')->insert([
            'name'=>'SQL Server',
            'pid'=>$pid1,
            'level'=>2,
            'sort'=>4,
            'project_id'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        $pid1 = DB::table('blog_article_types')->insertGetId([
            'name'=>'服务器',
            'pid'=>0,
            'level'=>1,
            'sort'=>2,
            'project_id'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        DB::table('blog_article_types')->insert([
            'name'=>'Linux',
            'pid'=>$pid1,
            'level'=>2,
            'sort'=>1,
            'project_id'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        DB::table('blog_article_types')->insert([
            'name'=>'Shell',
            'pid'=>$pid1,
            'level'=>2,
            'sort'=>2,
            'project_id'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        DB::table('blog_article_types')->insert([
            'name'=>'Nginx',
            'pid'=>$pid1,
            'level'=>2,
            'sort'=>3,
            'project_id'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        DB::table('blog_article_types')->insert([
            'name'=>'Docker',
            'pid'=>$pid1,
            'level'=>2,
            'sort'=>4,
            'project_id'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        DB::table('blog_article_types')->insert([
            'name'=>'Hadoop',
            'pid'=>$pid1,
            'level'=>2,
            'sort'=>5,
            'project_id'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        DB::table('blog_user_infos')->insert([
            'user_id' => 1,
            'project_id' => 1,
            'empirical_value'=>0,
            'status'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        $imageId1 = DB::table('auth_images')->insertGetId([
            'url' => '/upload/images/common/banner.png',
            'open' => 1,
            'status'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        DB::table('blog_pics')->insert([
            'project_id' => 1,
            'content' => 1,
            'url'=>'https://www.baidu.com',
            'image_id'=>$imageId1,
            'type'=>0,
            'status'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
		DB::table('blog_pics')->insert([
            'project_id' => 1,
            'content' => 1,
            'url'=>'https://www.baidu.com',
            'image_id'=>$imageId1,
            'type'=>0,
            'status'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        $imageId2 = DB::table('auth_images')->insertGetId([
            'url' => '/upload/images/common/banner1.png',
            'open' => 1,
            'status'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        $labelId1 = DB::table('blog_labels')->insertGetId([
            'project_id' => 1,
            'name' => 'php',
            'status'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        $labelId2 = DB::table('blog_labels')->insertGetId([
            'project_id' => 1,
            'name' => 'cms',
            'status'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        $labelId3 = DB::table('blog_labels')->insertGetId([
            'project_id' => 1,
            'name' => 'laravel',
            'status'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        $labelId4 = DB::table('blog_labels')->insertGetId([
            'project_id' => 1,
            'name' => 'vue',
            'status'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        $labelId5 = DB::table('blog_labels')->insertGetId([
            'project_id' => 1,
            'name' => 'nuxtJS',
            'status'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        $labelId6 = DB::table('blog_labels')->insertGetId([
            'project_id' => 1,
            'name' => 'elementUI',
            'status'=>1,
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        for($i=0;$i<20;$i++){
            $articleId = DB::table('blog_articles')->insertGetId([
                'type_id' => 2,
                'user_id' => 1,
                'project_id'=>1,
                'title'=>$i.'咪乐多cms博客项目正式上线',
                'description'=>"咪乐多CMS管理系统，是一款开源的CMS管理系统，为中小企业提供最佳的开发方案。",
            'content'=>"<p>咪乐多CMS管理系统</p>",
                'image_id'=>$imageId2,
                'status'=>1,
                'open'=>0,
                'created_at'=>date('Y-m-d H:i:s')
            ]);
            DB::table('blog_article_labels')->insert([
                'article_id' => $articleId,
                'label_id' => $labelId1
            ]);
            DB::table('blog_article_labels')->insert([
                'article_id' => $articleId,
                'label_id' => $labelId2
            ]);
            DB::table('blog_article_labels')->insert([
                'article_id' => $articleId,
                'label_id' => $labelId3
            ]);
            DB::table('blog_article_labels')->insert([
                'article_id' => $articleId,
                'label_id' => $labelId4
            ]);
            DB::table('blog_article_labels')->insert([
                'article_id' => $articleId,
                'label_id' => $labelId5
            ]);
            DB::table('blog_article_labels')->insert([
                'article_id' => $articleId,
                'label_id' => $labelId6
            ]);
        }
    }
}
