<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Book;

/**
 * BooksSearch represents the model behind the search form about `\app\models\Book`.
 */
class BooksSearch extends Book
{
    public function rules()
    {
        return [
            [['_id', 'filename', 'extension', 'create_dt', 'parse_status', 'hash'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Book::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere(['like', '_id', $this->_id])
            ->andFilterWhere(['like', 'filename', $this->filename])
            ->andFilterWhere(['like', 'extension', $this->extension])
            ->andFilterWhere(['like', 'create_dt', $this->create_dt])
            ->andFilterWhere(['like', 'parse_status', $this->parse_status])
            ->andFilterWhere(['like', 'hash', $this->hash]);

        return $dataProvider;
    }
}
