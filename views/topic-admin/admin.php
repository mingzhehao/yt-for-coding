<?php

use yii\helpers\Html;
use yii\grid\GridView;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\TopicAdminSearch $searchModel
 */

$this->title = '话题管理';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="topic-admin-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php  //echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('创建话题', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            'id',
            'title',
            //'content:ntext',
            'describe',
            'tags:ntext',
            'status',
            'create_time:datetime',
            //'update_time:datetime',
            'author_id',
            // 'viewcount',
            // 'up',
             'classify',

            ['class' => 'yii\grid\ActionColumn'],
            //['class' => 'yii\grid\DataColumn','attribute'=>'classify'],
            ['class' => 'yii\grid\CheckboxColumn'],
        ],
    ]); ?>

</div>
