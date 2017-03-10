<?php
/**
 * 协程任务
 *
 * @author camera360_server@camera360.com
 * @copyright Chengdu pinguo Technology Co.,Ltd.
 */

namespace PG\MSF\Server\CoreBase;

class CoroutineTask
{
    protected $stack;
    protected $routine;
    public $generatorContext;

    public function __construct(\Generator $routine, GeneratorContext $generatorContext)
    {
        $this->routine = $routine;
        $this->generatorContext = $generatorContext;
        $this->stack = new \SplStack();
    }

    /**
     * 协程调度
     */
    public function run()
    {
        $routine = &$this->routine;
        $flag = false;
        try {
            if (!$routine) {
                return;
            }
            $value = $routine->current();
            $flag = true;
            //嵌套的协程
            if ($value instanceof \Generator) {
                $this->generatorContext->addYieldStack($routine->key());
                $this->stack->push($routine);
                $routine = $value;
                return;
            }
            if ($value != null && $value instanceof ICoroutineBase) {
                $result = $value->getResult();
                if ($result !== CoroutineNull::getInstance()) {
                    $routine->send($result);
                }
                //嵌套的协程返回
                while (!$routine->valid() && !$this->stack->isEmpty()) {
                    $result = $routine->getReturn();
                    $this->routine = $this->stack->pop();
                    $this->routine->send($result);
                    $this->generatorContext->popYieldStack();
                }
            } else {
                if ($routine->valid()) {
                    $routine->send($value);
                } else {
                    $result = $routine->getReturn();
                    if (count($this->stack) > 0) {
                        $this->routine = $this->stack->pop();
                        $this->routine->send($result);
                    }
                }
            }
        } catch (\Exception $e) {
            if ($flag) {
                $this->generatorContext->addYieldStack($routine->key());
            }
            if (empty($value)) {
                $value = "";
            }
            $message = 'yield ' . str_replace(["\n", " ", "=>"], ["", "", " => "],
                    var_export($value, true)) . ' message: ' . $e->getMessage();
            $runTaskException = new CoroutineException($message, $e->getCode(), $e);
            $this->generatorContext->setErrorFile($runTaskException->getFile(), $runTaskException->getLine());
            $this->generatorContext->setErrorMessage($message);

            while (!$this->stack->isEmpty()) {
                $this->routine = $this->stack->pop();
                try {
                    $this->routine->throw($runTaskException);
                    break;
                } catch (\Exception $e) {

                }
            }

            if ($runTaskException instanceof SwooleException) {
                $runTaskException->setShowOther($this->generatorContext->getTraceStack(),
                    $this->generatorContext->getController());
            }
            if ($this->generatorContext->getController() != null) {
                call_user_func([$this->generatorContext->getController(), 'onExceptionHandle'], $e);
            } else {
                $routine->throw($runTaskException);
            }
        }
    }

    /**
     * [isFinished 判断该task是否完成]
     * @return boolean [description]
     */
    public function isFinished()
    {
        return $this->stack->isEmpty() && !$this->routine->valid();
    }

    public function getRoutine()
    {
        return $this->routine;
    }

    /**
     * 销毁
     */
    public function destroy()
    {
        $this->generatorContext->destroy();
        unset($this->generatorContext);
        unset($this->stack);
        unset($this->routine);
    }
}