<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Page;

/**
 * PageSearch represents the model behind the search form about `\app\models\Page`.
 */
class PageSearch extends Page
{
    public function rules()
    {
        return [
            [['_id', 'book_id', 'page', 'text', 'lang'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Page::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

		$this->_id && $query->andWhere(['_id' => new \MongoId($this->_id)]);
		$this->book_id && $query->andWhere(['book_id' => new \MongoId($this->book_id)]);
		foreach ($this->getAttributes() as $attr_name => $attr_value) {
			if ($attr_name === '_id' || $attr_name === 'book_id') {
				continue;
			}
        	if ($attr_value !== '' && $attr_value !== null) {
				$query->andWhere([$attr_name => $attr_value]);
			}
		}


        return $dataProvider;
    }
}
