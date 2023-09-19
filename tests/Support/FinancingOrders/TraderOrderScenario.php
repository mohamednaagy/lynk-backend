<?php

namespace Tests\Support\FinancingOrders;

use App\Enums\FinancingOrderHistory;
use App\Models\TraderHistory;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionaryNode;

class TraderOrderScenario
{
    protected StepHistoriesDictionary $dictionary;

    protected array $traderHistory;

    protected array $orderedSteps;

    protected function __construct(protected TraderOrder $traderOrder)
    {

        $this->dictionary = new StepHistoriesDictionary($this->traderOrder->provider, $this->traderOrder->version);

        $this->traderHistory = trader_step_histories($this->traderOrder->provider, $traderOrder->version);

        $this->orderedSteps = array_keys($this->traderHistory);
    }

    public static function of(TraderOrder $traderOrder)
    {
        return new static(
            TraderOrder::query()
                ->where('id', $traderOrder->id)
                ->withLastHistoryAction()
                ->first()
        );
    }

    public function getTraderOrder()
    {
        return $this->traderOrder;
    }

    public function reset()
    {
        TraderHistory::withoutEvents(function () {
            $this->traderOrder
                ->traderHistories()
                ->where('action', '!=', FinancingOrderHistory::GetTtiId)
                ->delete();
        });

        $this->loadHistoriesAndSetLastAction();

        return $this;
    }

    public function moveToStep(string $destinationStep)
    {
        $currentStep = $this->dictionary->getStepByHistory($this->traderOrder->last_history_action);
        $nextStep = $this->dictionary->getNextStepOf($currentStep->step);

        if (! $currentStep instanceof StepHistoriesDictionaryNode || ! $nextStep instanceof StepHistoriesDictionaryNode) {
            throw new \Exception('Current/next step doesn\'t exists');
        }

        $lastHistoryOfDestinationStep = end($this->traderHistory[$destinationStep]);

        $this->moveToHistory($lastHistoryOfDestinationStep);

        return $this;
    }

    public function moveToHistory(int $destinationHistory)
    {
        $currentStep = $this->dictionary->getStepByHistory($this->traderOrder->last_history_action);
        $destinationStep = $this->dictionary->getStepByHistory($destinationHistory);

        if (! $currentStep instanceof StepHistoriesDictionaryNode || ! $destinationStep instanceof StepHistoriesDictionaryNode) {
            throw new \Exception('Step doesn\'t exists');
        }

        $positionOfCurrentStep = array_search($currentStep->step, $this->orderedSteps);

        if (
            $positionOfCurrentStep !== false
            && ($positionOfCurrentStep + 1) === count($this->orderedSteps)
            && $this->traderOrder->checkOrderStepComplete($currentStep->step)
        ) {
            throw new \Exception('The current step is the last one.');
        }

        $positionOfNextStep = array_search($destinationStep->step, $this->orderedSteps);
        if (
            $positionOfNextStep !== false
            && $positionOfCurrentStep >= $positionOfNextStep
            && $this->traderOrder->checkOrderStepComplete($destinationStep->step)
        ) {
            throw new \Exception('Cannot move to next stage because it is the same or before the current one.');
        }

        $createAt = now();

        for ($i = $positionOfCurrentStep; $i <= $positionOfNextStep; $i++) {
            $step = $this->orderedSteps[$i];
            $histories = $this->traderHistory[$step];

            if (
                $step === $currentStep->step &&
                ! $this->traderOrder->load('traderHistories')->checkOrderStepComplete($currentStep->step)
            ) {
                $lastActionHistoryPosition = array_search(
                    $this->traderOrder->traderHistories()->latest('id')->first()->action,
                    $currentStep->histories
                );
                $histories = collect($histories)->slice($lastActionHistoryPosition + 1)->values();
            }

            TraderHistory::withoutEvents(function () use ($destinationHistory, $histories, $createAt) {
                foreach ($histories as $history) {
                    $this->traderOrder->traderHistories()->updateOrCreate([
                        'action' => $history,
                    ], [
                        'created_at' => $createAt,
                    ]);

                    if ($history === $destinationHistory) {
                        break;
                    }

                    $createAt = $createAt->addMinutes(1);
                }
            });
        }

        $this->loadHistoriesAndSetLastAction();

        return $this;
    }

    protected function loadHistoriesAndSetLastAction()
    {
        $this->traderOrder->load('traderHistories');

        $this->traderOrder->setAttribute('last_history_action', TraderHistory::select('action')
            ->where('trader_order_id', $this->traderOrder->id)
            ->latest('id')
            ->take(1));
    }
}
