<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\models\BaseModel;
use app\models\Tag;
use yii\data\ActiveDataProvider;

/**
 * This is the model class for table "humour".
 *
 * @property integer $id
 * @property string $title
 * @property string $content
 * @property string $describe
 * @property string $tags
 * @property integer $status
 * @property integer $create_time
 * @property integer $update_time
 * @property integer $author_id
 * @property integer $viewcount
 * @property integer $up
 * @property integer $collect
 * @property integer $classify
 */
class Humour extends BaseModel
{
    private $_oldTags;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'humour';
    }

    public static function getClassName()
    {
        return 'humour';
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'content', 'describe', 'status', 'classify'], 'required'],
            [['content', 'tags'], 'string'],
            [['status', 'classify'], 'integer'],
            [['title'], 'string', 'max' => 128],
            [['title', 'content', 'describe', 'tags'], 'safe'],
            [['describe'], 'string', 'max' => 1024]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => '标题',
            'content' => '内容',
            'describe' => '描述',
            'tags' => '标签',
            'status' => '状态',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
            'author_id' => '作者ID',
            'viewcount' => '查阅次数',
            'up' => '点赞',
            'collect' => '收藏',
            'classify' => '分类',
        ];
    }
 
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Humour::find();
        $queryParams = Yii::$app->request->getQueryParams();
        $sort = trim(isset($queryParams['sort'])?$queryParams['sort']:'update_time');
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ["$sort"=>SORT_DESC]],
            //'sort'=> ['defaultOrder' => ["update_time"=>SORT_DESC]],
            //'sort'  => [
            //    'attributes' => [
            //            'classify'=>SORT_DESC,
            //    ]
            //],
        ]);
        /*自定义处理classify存在情况下的搜索*/
        if(isset($params['classify']) && !empty($params['classify']))
        {
            $this->classify = trim(trim($params['classify']));
        }
        elseif(isset($params['tags']) &&!empty($params['tags']))
        {
            $this->tags=trim(trim($params['tags']));
        }
        else
        {
            if (!($this->load($params) && $this->validate())) {
                return $dataProvider;
            }
        }   
        /*********END*************************/

        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
            'create_time' => $this->create_time,
            'update_time' => $this->update_time,
            'author_id' => $this->author_id,
            'viewcount' => $this->viewcount,
            'up' => $this->up,
            'classify' => $this->classify,
        ]);
        $query->andFilterWhere(['like', 'title', $this->title])
            ->andFilterWhere(['like', 'content', $this->content])
            ->andFilterWhere(['like', 'describe', $this->describe])
            ->andFilterWhere(['like', 'tags', $this->tags]);

        return $dataProvider;
    }
    /**
     * This is invoked when a record is populated with data from a find() call.
     */
    public function afterFind()
    {
        parent::afterFind();
        $this->_oldTags=$this->tags;
    }

/**
     * This is invoked before the record is saved.
     * @return boolean whether the record should be saved.
     */
    public function beforeSave($insert)
    {
        if(parent::beforeSave($insert))
        {
            if($this->isNewRecord)
            {
                $this->create_time=date("Y-m-d H:i:s",time());
                $this->author_id=Yii::$app->user->id;
            }
            return true;
        }
        else
            return false;
    }

    /**
     * This is invoked after the record is saved.
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        Tag::updateFrequency($this->_oldTags, $this->tags,strtolower($this->getClassName()));
    }

    /**
     * This is invoked after the record is deleted.
     */
    public function afterDelete()
    {
        parent::afterDelete();
        Comment::deleteAll(['post_id'=>$this->id,'classify_type'=>strtolower($this->getClassName())]);
        Tag::updateFrequency($this->tags, '',strtolower($this->getClassName()));
    }
   

    /**
     * Adds a new comment to this news.
     * This method will set status and http_news_fields(url, data) of the comment accordingly.
     * @param Comment the comment to be added
     * @return boolean whether the comment is saved successfully
     */
    public function addComment($comment)
    {
        if(Yii::$app->params['commentNeedApproval'])
            $comment->status=Comment::STATUS_PENDING;
        else
            $comment->status=Comment::STATUS_APPROVED;
        $comment->post_id=$this->id;
        return $comment->save();
    }


    /*
     *  获取所有内容，存在分类标识输出其相关内容 
     */
    public function getClassify($params=null)
    {
        if(empty($params['classify']))
        {
            $connection = Yii::$app->db;
            $command = $connection->createCommand('SELECT classify,count(classify) as counts FROM '.self::tableName().' group by classify ');
            $postCount = $command->queryAll();
        }
        else
        {
            $connection = Yii::$app->db;
            $command = $connection->createCommand('SELECT classify,count(classify) as counts FROM '.self::tableName().' where classify = "'.$params['classify'].'" group by classify ');
            $postCount = $command->queryAll();
        }
        return $postCount;
    }


    /**
     * @param integer the maximum number of comments that should be returned
     * @return array the most recently added comments
     */
    public static function getHotPosts($limit=10)
    {
        $connection = Yii::$app->db;
        $command = $connection->createCommand('SELECT * FROM '.self::tableName().' order by viewcount desc limit 10');
        $postCount = $command->queryAll();
        return $postCount;
    }

    /*
     * 获取当前文章url
     */
    public static function getUrl()
    {
        /*
            页面中如下进行输出
            <?= Html::encode($model->url) ?>
        */
        return Yii::$app->urlManager->createUrl(['humour/view', 'id' => $this->id,'title' => $this->title]);
    }


    /**
     * 关联数据 获取当前文章所属用户的username
     */
    public function getUname()
    {
        // 关联的一对一关系
        // select * from user where id = author_id;
        return $this->hasOne(User::className(), ['id' => 'author_id']);
    }

    /*
     * 关联数据 获取当前文章评论总数
     */
    public function getComment()
    {
        return $this->hasMany(Comment::className(),['post_id' => 'id']);
    }  

}
