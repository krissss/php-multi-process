<?php

namespace Kriss\MultiProcessTests;

use Kriss\MultiProcess\MultiProcess;
use Kriss\MultiProcess\PendingTaskProcess;

class SymfonyConsoleTestClass
{
    private $user;

    public static function makeResults()
    {
        $self = new self();
        $self->user = 'user';

        $result = 'new ok';
        return MultiProcess::create()
            // 数组形式的静态调用
            ->add([SymfonyConsoleTestClass::class, 'handle'], 'p1')
            // Closure
            ->add(fn() => SymfonyConsoleTestClass::handle(), 'p2')
            // Closure 支持 use
            ->add(fn() => SymfonyConsoleTestClass::handle($result), 'p3')
            // 数组形式的对象调用
            ->add([$self, 'getUser'], 'p4')
            // Closure 支持 $this
            ->add(fn() => $self->user, 'p5')
            // 返回数组形式
            ->add(fn() => [1, 2], 'p6')
            // 返回对象
            ->add(fn() => $self, 'p7')
            // 使用 PendingTaskProcess
            ->add(PendingTaskProcess::createFromTask(fn() => 'ok'), 'p8')
            ->wait();
    }

    public static function makeForPendingTaskProcess()
    {
        return MultiProcess::create()
            ->add(fn() => 'ok', 'p1')
            ->wait();
    }

    public static function handle($result = null)
    {
        return $result ?: 'ok';
    }

    public function getUser()
    {
        return $this->user;
    }
}