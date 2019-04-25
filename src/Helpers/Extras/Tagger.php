<?php
/**
 * Created by PhpStorm.
 * User: joshgulledge
 * Date: 7/26/18
 * Time: 10:46 AM
 */

namespace LCI\MODX\Stockpile\Helpers\Extras;

use LCI\MODX\Stockpile\Helpers\PaginationHelper;
use \modX;
use \Tagger as modxTagger;

class Tagger
{
    use PaginationHelper;

    /** @var modX */
    protected $modx;

    /** @var modxTagger */
    protected $modxTagger;

    /** @var array  */
    protected $return_group_columns = ['id', 'alias', 'name', 'field_type', 'position'];

    /**
     * Tagger constructor.
     * @param modX $modx
     */
    public function __construct(modX $modx)
    {
        $this->modx = $modx;
        $this->loadTagger();

        // Set pagination to nearly unlimited by default:
        $this
            ->setSortColumn('Group.name')
            ->setPerPage(2000);
    }

    /**
     * @return bool
     */
    public function isInstalled()
    {
        if (is_object($this->modxTagger) && $this->modxTagger instanceof modxTagger) {
            return true;
        }

        return false;
    }

    /**
     * @param string|int $group ~ the name or the ID
     * @return array
     */
    public function getGroupTags($group)
    {
        $this->loadTagger();
        $query = $this->modx->newQuery('TaggerTag');

        $query->leftJoin('TaggerGroup', 'Group');

        $query->select($this->modx->getSelectColumns('TaggerTag', 'TaggerTag'));
        $query->select('Group.name as `groupName`');

        if (is_numeric($group)) {
            // ID
            $query->where([
                'group' => $group
            ]);
        } else {
            $query->where([
                'Group.name' => $group
            ]);
        }

        $query = $this
                ->addPagination($query);

        $query->prepare();
        $sql = $query->toSql();

        $results = $this->modx->query($sql);

        $tags = [];

        while ($tag = $results->fetch(\PDO::FETCH_ASSOC)) {
            $tags[] = $tag;
        }

        $data = $this->getPaginationData();

        $data['count'] = count($tags);
        $data['items'] = $tags;

        return $data;
    }

    /**
     * @param int $resource_id
     * @return array
     */
    public function getResourceTags($resource_id)
    {
        $this->loadTagger();
        $tags = [];
        [
            'group-alias' => [
                'tags' => [],
                'columns' => [],
                'tag-ids' => []
            ]
        ];
        // get resource Group
        // Get all tags for resource:

        $query = $this->modx->newQuery('TaggerTag');

        $query->leftJoin('TaggerTagResource', 'Resources');
        $query->leftJoin('TaggerGroup', 'Group');
        $query->leftJoin('modResource', 'Resource', ['Resources.resource = Resource.id']);

        $query->select($this->modx->getSelectColumns('TaggerTag', 'TaggerTag'));
        $query->select($this->modx->getSelectColumns('TaggerGroup', 'Group', 'group_'));

        $query->where(['Resources.resource' => $resource_id]);

        $query->prepare();
        $sql = $query->toSql();

        $results = $this->modx->query($sql);

        while ($tag = $results->fetch(\PDO::FETCH_ASSOC)) {

            $tag_columns = $group_columns = [];
            foreach ($tag as $name => $value) {
                if (strpos(' '.$name, 'group_') === 1) {
                    $group_column = substr($name, strlen('group_'));
                    if (in_array($group_column, $this->return_group_columns)) {
                        $group_columns[$group_column] = $value;
                    }

                } else {
                    $tag_columns[$name] = $value;
                }
            }

            if ( !isset($tags[$tag['group_alias']])) {
                $tags[$tag['group_alias']] = [
                    'columns' => $group_columns,
                    'tags' => [],
                    'tag-ids' => []
                ];
            }

            $tags[$tag['group_alias']]['tags'][] = $tag_columns;
            $tags[$tag['group_alias']]['tag-ids'][] = $tag['id'];

        }

        return $tags;
    }

    /**
     *
     */
    protected function loadTagger()
    {
        if (!is_object($this->modxTagger)) {
            $tagger_path = $this->modx->getOption('tagger.core_path', null, $this->modx->getOption('core_path') . 'components/tagger/') . 'model/tagger/';
            if (is_dir($tagger_path)) {
                /** @var modxTagger $tagger */
                $this->modxTagger = $this->modx->getService('tagger', 'Tagger', $tagger_path, []);
            }
        }
    }
}