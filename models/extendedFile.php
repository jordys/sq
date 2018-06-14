<?php

class extendedFile extends file {
    public function read($type = null) {
        parent::read($type);

        $this->order('created');

        $i = 1;
        foreach ($this->data as $item) {
            if (empty($item->id)) {
                break;
            }

            $model = sq::model('sq_files')->find(['id' => $item->id]);
            if (empty($model->id)) {
                $model->save([
                    'id' => $item->id,
                    'sort' => $i
                ]);
            }

            $item->sort = $model->sort;
            $i++;
        }

        return $this->order('sort', 'DESC');
    }

    public function delete($where = null) {
        parent::delete($where);

        $i = 0;
        foreach ($this->data as $item) {
            sq::model('sq_files')->delete(['id' => $this->id]);
        }

        return $this;
    }
}
