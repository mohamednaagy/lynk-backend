<?php

namespace App\Support\FinancingOrders\StepAndHistories;

use App\Enums\MurabhaStep;

class StepHistoriesDictionary
{
    public \SplDoublyLinkedList $dictionaryNodeList;

    public function __construct()
    {
        $this->dictionaryNodeList = new \SplDoublyLinkedList();

        foreach (MurabhaStep::getValues() as $status => $histories) {
            $this->dictionaryNodeList->push(new StepHistoriesDictionaryNode($status, $histories));
        }
    }

    public function getPreviousStepOf($status)
    {
        $this->dictionaryNodeList->rewind();
        while ($this->dictionaryNodeList->valid()) {
            if ($this->dictionaryNodeList->current()->status == $status) {
                $this->dictionaryNodeList->prev();

                return $this->dictionaryNodeList->current();
            }
            $this->dictionaryNodeList->next();
        }
    }

    public function getNextStepOf($status)
    {
        $this->dictionaryNodeList->rewind();
        while ($this->dictionaryNodeList->valid()) {
            if ($this->dictionaryNodeList->current()->status == $status) {
                $this->dictionaryNodeList->next();

                return $this->dictionaryNodeList->current();
            }
            $this->dictionaryNodeList->next();
        }
    }

    public function getStepOf($status)
    {
        $this->dictionaryNodeList->rewind();
        while ($this->dictionaryNodeList->valid()) {
            if ($this->dictionaryNodeList->current()->status == $status) {
                return $this->dictionaryNodeList->current();
            }
            $this->dictionaryNodeList->next();
        }
    }

    public function getStepByHistory($history)
    {
        $this->dictionaryNodeList->rewind();
        while ($this->dictionaryNodeList->valid()) {
            if (in_array($history, $this->dictionaryNodeList->current()->histories)) {
                return $this->dictionaryNodeList->current();
            }

            $this->dictionaryNodeList->next();
        }
    }
}
