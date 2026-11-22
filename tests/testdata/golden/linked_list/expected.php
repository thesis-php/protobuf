<?php

declare(strict_types=1);

// The objects that the golden files of this directory stand for, of the classes of ../../reflection/linked_list.php.

return [
    'empty' => new LinkedList(),
    'one_node' => new LinkedList(new Element('only')),
    'three_nodes' => new LinkedList(new Element('x', new Element('y', new Element('z')))),
    'eight_nodes' => new LinkedList(
        new Element('1', new Element('два', new Element('three', new Element('4', new Element(
            '五',
            new Element('six', new Element('7', new Element('🐘'))),
        ))))),
    ),
];
