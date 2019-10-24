<?php
/**
 * Created by PhpStorm.
 * User: zhaopeng
 * Date: 2019/10/23
 * Time: 下午7:11
 */

const MAX_LEVEL = 16;
/**
 * Class SkipNode  跳表节点
 */
class SkipNode
{
    public $data = -1;  // 默认值为-1 头结点都是-1 没有实质性的作用
    public $maxLevel = 0; // 节点的最大层数，暂时没有实质性作用
    /**
     * @var SkipNode[]
     */
    public $forwards = [];  // 指向向前的节点数组，$forwards[0] 表示最底层的包含所有数据的链表 $forwards[1] 第一层的索引 ......以此类推
}

/**
 * 跳表
 * Class SkipList
 */
class SkipList
{
    /**
     * @var SkipNode
     */
    private $head;  // 头

    private $curMaxlevel = 1; // 当前最大的层数

    const SKIPLIST_P = 5;  // 随机获取level 中间值 下边获取level方法会介绍用途

    public function __construct()
    {
        $this->setHead();
    }

    /**
     * 设置头 如果为空则默认赋值 SkipNode 对象
     * @param SkipNode $head
     */
    public function setHead(SkipNode $head = null): void
    {
        $this->head = empty($head) ? new SkipNode() : $head;
    }

    /**
     * 查找跳表中的某个值
     * @param int $value
     * @return null|SkipNode
     */
    public function find(int $value)
    {
        $p = $this->head; // 首先将指针指向头的地址
        echo $p->data;  // 输出头的值
        // 从最顶层的head->forwards[$this->curMaxlevel - 1] 开始 判断data < value
        // 小于的话将 $p->forwards[$i] 赋值给当$p
        for ($i = $this->curMaxlevel - 1; $i >= 0; $i--) {
            while (isset($p->forwards[$i]) && $p->forwards[$i]->data < $value) {
                $p = $p->forwards[$i];
                echo '---->';
                echo $p->data;
            }
        }

        if (isset($p->forwards[0]) && $p->forwards[0]->data == $value) {
            return $p->forwards[0];
        } else {
            return null;
        }
    }

    /**
     * 跳表插入数据
     * @param int $value
     */
    public function insert(int $value)
    {
        $level = $this->getLevel(); // 当前value的层数

        /**
         * 生成新的跳表节点
         */
        $node = new SkipNode();
        $node->data = $value;
        $node->maxLevel = $level;

        $p = $this->head;
        /**
         * 从$level层开始
         * 保存每一层的离value值最近的节点
         * @var SkipNode[];
         */
        $update = [];
        for ($i = $level - 1; $i >= 0; $i--) {
            while ((isset($p->forwards[$i]) && $p->forwards[$i]->data < $value)) {
                $p = $p->forwards[$i];
            }

            $update[$i] = $p;
        }

        /**
         * 循环将当前节点 指向 每一层保存的前置节点的指向($update[$i]->forwards[$i])
         * 每一层保存的前置节点指向当前节点
         */
        for ($i = 0; $i < $level; $i++) {
            $node->forwards[$i] = isset($update[$i]->forwards[$i]) ? $update[$i]->forwards[$i] : null;
            $update[$i]->forwards[$i] = $node;
        }

        // 如果当前值的层数大于 最大的层数 则修改最大的层数
        if ($level > $this->curMaxlevel) $this->curMaxlevel = $level;
    }

    /**
     * 删除某个值的节点
     * @param int $value
     */
    public function delete(int $value)
    {
        $p = $this->head;

        /**
         * 保存每一层离 value 最近的skipNode的对象
         */
        $update = [];
        for ($i = $this->curMaxlevel - 1; $i >= 0; $i--) {
            while (isset($p->forwards[$i]) && $p->forwards[$i]->data < $value) {
                $p = $p->forwards[$i];
            }

            if (isset($p->forwards[$i]) && $p->forwards[$i]->data == $value) {
                $update[$i] = $p;
            }
        }

        /**
         * 循环update 数组 将节点的 向前指针 指向(-->) 向前向前 对象的指针
         */
        for ($i = $this->curMaxlevel - 1; $i >= 0; $i--) {
            if (isset($update[$i])) {

                $update[$i]->forwards[$i] = isset($update[$i]->forwards[$i]) ? $update[$i]->forwards[$i]->forwards[$i] : null;
            }
        }

        /**
         * 循环每一层看是否某一层头结点指向为 NULL 如果是 则将最大层数 减1
         */
        while ($this->curMaxlevel > 1 && empty($this->head->forwards[$this->curMaxlevel - 1])) {
            $this->curMaxlevel--;
        }
    }

    /**
     * 获取随机层数
     * 最底层必须插入 所以 概率为1
     * 第一层索引有50%概率插入
     * 第二层索引有25%概率插入
     * 第三层索引有12.5%概率插入
     * ......
     * 以此类推 上一层索引是下一层索引的1/2
     * @return int
     */
    private function getLevel()
    {
        $level = 1;

        /**
         *  mt_rand()返回1-10的随机数
         *  随机数 < 5的概率为 1/2 所以 level=2 的概率为 50%
         *  一直循环 每次 < 5的概率都为 1/2 所以 每一次的概率为 上一次的 50% eg.level=3 为 50% * 1/2 = 25%
         *  ....
         */
        while (mt_rand(1, 10) < self::SKIPLIST_P && $level < MAX_LEVEL) {
            $level += 1;
        }

        return $level;
    }

    public function test()
    {
        $this->insert(2);
        $this->insert(5);
        $this->insert(4);
        $this->insert(3);
        $this->insert(1);
        $this->insert(7);
        $this->insert(10);
        $this->insert(16);
        $this->insert(14);
        $this->putAll();
    }

    /**
     * 输出所有的数据
     */
    public function putAll()
    {
        echo PHP_EOL;
        echo PHP_EOL;
        $i = $this->curMaxlevel - 1;
        for (; $i >= 0; $i--) {
            echo $this->head->data;
            echo "------>";
            $p = $this->head->forwards[$i];
            if (isset($p)) {
                while (isset($p)) {
                    echo $p->data;
                    echo "------>";
                    $p = isset($p->forwards[$i]) ? $p->forwards[$i] : null;
                }
            }

            echo 'NULL';
            echo PHP_EOL;
        }

        echo PHP_EOL;
    }

    public function main()
    {
        $this->test();
        $this->delete(10);
        $this->putAll();
    }
}

$p = new SkipList();
$p->main();