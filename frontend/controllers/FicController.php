<?php

namespace frontend\controllers;

use common\models\Fiction;
use common\models\Http;
use Goutte\Client;
use yii\base\Exception;
use yii\helpers\Html;
use Yii;
use yii\web\Response;

class FicController extends BaseController
{
    //小说章节目录页
    public function actionList()
    {
        $dk = $this->get('dk');
        $fk = $this->get('fk');
        $fiction = Fiction::getFiction($dk, $fk);
        if ($dk && $fk && $fiction) {
            $list = Fiction::getFictionList($dk, $fk);
            return $this->render('list', [
                'fiction' => $fiction,
                'list' => $list,
                'dk' => $dk,
                'fk' => $fk,
            ]);
        } else {
            $this->err404('页面未找到');
        }
    }

    //小说详情页
    public function actionDetail()
    {
        $dk = $this->get('dk');
        $fk = $this->get('fk');
        $url = base64_decode($this->get('url'));//解密url
        $text = $this->get('text');
        $data = Fiction::getFictionTitleAndNum($dk, $fk, $url);
        $current = $data['current'];
        $text = $text ? $text : $data['title'];
        $fiction = Fiction::getFiction($dk, $fk);
        if ($fiction) {
            $client = new Client();
            $crawler = $client->request('GET', $url);
            try {
                if ($crawler) {
                    $detail = $crawler->filter($fiction['fiction_detail_rule']);
                    if ($detail) {
                        $content = '';
                        global $content;
                        $detail->each(function ($node) use ($content) {
                            global $content;
                            $text = $node->html();
                            $text = preg_replace('/<script.*?>.*?<\/script>/', '', $text);
                            $text = preg_replace('/(<br\s?\/?>){1,}/', '<br/><br/>', $text);
                            $text = strip_tags($text, '<p><div><br>');
                            $content = $content . $text;
                        });
                    }
                }
            } catch (Exception $e) {
                //todo 处理查找失败
            }
            $content = isset($content) ? $content : '未获取到指定章节';

            return $this->render('detail', [
                'content' => $content,
                'fiction' => $fiction,
                'text' => $text,
                'dk' => $dk,
                'fk' => $fk,
                'url' => $url,
                'current' => $current,
            ]);
        } else {
            $this->err404('页面未找到');
        }
    }

    //ajax获取上一章、下一章
    public function actionPn()
    {
        $dk = $this->get('dk');
        $fk = $this->get('fk');
        $url = base64_decode($this->get('url'));
        if (Yii::$app->request->isAjax) {
            $res = Fiction::getPrevAndNext($dk, $fk, $url);
            return $res;
        } else {
            $this->err404();
        }
    }
}