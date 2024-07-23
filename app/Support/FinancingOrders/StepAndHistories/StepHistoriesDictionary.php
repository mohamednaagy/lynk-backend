<?php

namespace App\Support\FinancingOrders\StepAndHistories;

use App\Models\TraderOrder;

class StepHistoriesDictionary
{
    public \SplDoublyLinkedList $dictionaryNodeList;

    public function __construct($trader = null, $version = null)
    {
        $this->dictionaryNodeList = new \SplDoublyLinkedList();
        $trader = $trader ?? config('trader.default');
        $version = $version ?? get_latest_version_of_trader($trader);

        foreach (trader_step_histories($trader, $version) as $step => $histories) {
            $this->dictionaryNodeList->push(new StepHistoriesDictionaryNode($step, $histories));
        }
    }

    public function getPreviousStepOf($step)
    {
        $this->dictionaryNodeList->rewind();
        while ($this->dictionaryNodeList->valid()) {
            if ($this->dictionaryNodeList->current()->step == $step) {
                $this->dictionaryNodeList->prev();

                return $this->dictionaryNodeList->current();
            }
            $this->dictionaryNodeList->next();
        }
    }

    public function getNextStepOf($step)
    {
        $this->dictionaryNodeList->rewind();
        while ($this->dictionaryNodeList->valid()) {
            if ($this->dictionaryNodeList->current()->step == $step) {
                $this->dictionaryNodeList->next();

                return $this->dictionaryNodeList->current();
            }
            $this->dictionaryNodeList->next();
        }
    }

    public function getStepOf($step)
    {
        $this->dictionaryNodeList->rewind();
        while ($this->dictionaryNodeList->valid()) {
            if ($this->dictionaryNodeList->current()->step == $step) {
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

    public function getCompletedStepByHistory($history)
    {
        $this->dictionaryNodeList->rewind();
        while ($this->dictionaryNodeList->valid()) {
            if ($history == end($this->dictionaryNodeList->current()->histories)) {
                return $this->dictionaryNodeList->current();
            }

            $this->dictionaryNodeList->next();
        }
    }

    public function getCompletedStepOrPreviousByHistory($history)
    {
        $this->dictionaryNodeList->rewind();
        while ($this->dictionaryNodeList->valid()) {
            if ($history == end($this->dictionaryNodeList->current()->histories)) {
                return $this->dictionaryNodeList->current();
            }

            if (in_array($history, $this->dictionaryNodeList->current()->histories)) {
                $this->dictionaryNodeList->prev();

                return $this->dictionaryNodeList->current();
            }
            $this->dictionaryNodeList->next();
        }
    }

    public function getLastCompletedStepOf(TraderOrder $traderOrder)
    {
        $histories = $traderOrder->traderHistories()->pluck('action')->toArray();

        $this->dictionaryNodeList->rewind();
        while ($this->dictionaryNodeList->valid()) {
            $lastHistoryOfStep = end($this->dictionaryNodeList->current()->histories);

            if (in_array($lastHistoryOfStep, $histories)) {
                $this->dictionaryNodeList->next();
            } else {
                $this->dictionaryNodeList->prev();

                return $this->dictionaryNodeList->current();
            }
        }
    }

    public function getCancelStep(TraderOrder $traderOrder)
    {
        $last_completed_step = $this->getLastCompletedStepOf($traderOrder);

        return is_null($last_completed_step) ? $this->getStepOf($traderOrder->currentStep) : $this->getNextStepOf($last_completed_step->step);
    }
}
