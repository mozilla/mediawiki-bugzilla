<?php

require '../../Bugzilla.class.php';
require '../../BugzillaQuery.class.php';

class BugzillaQueryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider prepareOptionsProvider
     *
     * @param string $json     JSON options structure defined in <bugzilla /> element.
     * @param array  $default  default fields to include in query
     * @param array  $expected resulting options array
    */
    public function testPrepareOptions($json, $default, $expected)
    {
        $q = BugzillaQuery::create('bug', $json, 'title');
        $this->assertEquals($expected, $q->prepare_options($json, $default));
    }

    public function prepareOptionsProvider()
    {
        $default_includes = ['incA', 'incB'];
        return [
            [
                '',
                [],
                ['include_fields' => []]
            ],
            [
                " { \n } ",
                ['default'],
                ['include_fields' => ['default']]
            ],
            [
                '{"other":"options"}',
                $default_includes,
                [
                    'include_fields' => $default_includes,
                    'other' => 'options'
                ]
            ],
            [
                '{"include_fields": ["C"]}',
                $default_includes,
                [
                    'include_fields' => ['C']
                ]
            ],
            [
                '{"include_fields": ["json", "array"]}',
                $default_includes,
                [
                    'include_fields' => ['json', 'array']
                ]
            ],
            [
                '{"include_fields": "json,string"}',
                $default_includes,
                [
                    'include_fields' => ['json', 'string']
                ]
            ],
            [
                'invalid JSON',
                $default_includes,
                null
            ],
        ];
    }

    public function testPrepareOptionsError()
    {
        $q = BugzillaQuery::create('bug', 'invalid JSON', 'title');
        $this->assertEquals(null, $q->prepare_options('invalid JSON', ['A', 'B']));
        $this->assertEquals('Query options must be valid JSON.', $q->error);
    }

    /**
     * @dataProvider rebaseFieldsProvider
    */
    public function testRebaseFields($request, $synthetic, $expected)
    {
        $q = BugzillaQuery::create('bug', '{}', 'title');
        $this->assertEquals($expected, $q->rebase_fields($request, $synthetic));
    }

    public function rebaseFieldsProvider()
    {
        return [
            [ ['A', 'B', 'C'], ['A', 'B'], ['A', 'B', 'C'] ],
            [ ['A'],           ['A', 'B'], ['A', 'B']      ],
            [ ['C'],           ['B', 'A'], ['A', 'B', 'C'] ],
        ];
    }

}
