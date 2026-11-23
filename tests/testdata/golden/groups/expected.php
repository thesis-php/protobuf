<?php

declare(strict_types=1);

// The objects that the golden files of this directory stand for, of the classes of ../../reflection/groups.php.

$groupAndMessage = new Groups(
    single: new GroupLeaf(9, 'same', new GroupLeaf(10)),
    plain: new GroupLeaf(9, 'same', new GroupLeaf(10)),
);

$tree = new Groups(
    tree: new GroupTree(1, new Groups(
        single: new GroupLeaf(2),
        tree: new GroupTree(2, new Groups(
            item: [new GroupLeaf(name: 'leaf')],
            after: -3,
        )),
    )),
    after: 4,
);

return [
    'empty' => new Groups(),
    'single' => new Groups(single: new GroupLeaf(5, 'five'), after: 7),
    'empty_groups' => new Groups(single: new GroupLeaf(), item: [new GroupLeaf(), new GroupLeaf()], tree: new GroupTree()),
    'nested' => new Groups(single: new GroupLeaf(1, inner: new GroupLeaf(2, 'inner'))),
    'repeated' => new Groups(item: [
        new GroupLeaf(1),
        new GroupLeaf(name: 'two'),
        new GroupLeaf(3, 'три', new GroupLeaf(-1)),
    ]),
    'group_and_message' => $groupAndMessage,
    'tree' => $tree,
    'decode-only/group_and_message' => $groupAndMessage,
    'decode-only/tree' => $tree,
];
