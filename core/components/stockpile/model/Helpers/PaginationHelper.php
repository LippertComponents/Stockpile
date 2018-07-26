<?php
/**
 * Created by PhpStorm.
 * User: joshgulledge
 * Date: 7/20/18
 * Time: 10:26 AM
 */

namespace Lci\Helpers;


trait PaginationHelper
{
    /** @var int  */
    protected $page = 1;

    /** @var int  */
    protected $per_page = 15;

    /** @var string  */
    protected $sort_column = 'modResource.publishedon';

    /** @var string  */
    protected $sort_dir = 'DESC';

    /** @var array  */
    protected $pagination_data = [];

    /**
     * @return int
     */
    public function getPerPage()
    {
        return $this->per_page;
    }

    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @return string
     */
    public function getSortColumn()
    {
        return $this->sort_column;
    }

    /**
     * @return string
     */
    public function getSortDir()
    {
        return $this->sort_dir;
    }

    /**
     * @param int $per_page
     * @return PaginationHelper
     */
    public function setPerPage($per_page)
    {
        $this->per_page = $per_page;
        return $this;
    }

    /**
     * @param int $page
     * @return PaginationHelper
     */
    public function setPage($page)
    {
        $this->page = $page;
        return $this;
    }

    /**
     * @param string $sort_column
     * @return PaginationHelper
     */
    public function setSortColumn($sort_column)
    {
        $this->sort_column = $sort_column;
        return $this;
    }

    /**
     * @param string $sort_dir
     * @return PaginationHelper
     */
    public function setSortDir($sort_dir)
    {
        $this->sort_dir = $sort_dir;
        return $this;
    }

    /**
     * @param \xPDOQuery $query
     * @return \xPDOQuery
     */
    protected function addPagination(\xPDOQuery $query)
    {
        $query->sortBy($this->getSortColumn(), $this->getSortDir());

        $offset = (int)$this->getPage() - 1;

        $total = $this->modx->getCount($query->getClass(), $query);

        $this->pagination_data = [
            'total_count' => $total,
            'current_page' => $this->getPage(),
            'total_pages' => ceil($total / $this->getPerpage()),
            'limit' => $this->getPerpage()
        ];

        $query->limit($this->getPerpage(), ($offset < 0 ? 0 : $offset * $this->getPerpage()) );

        return $query;
    }

    /**
     * @return array
     */
    public function getPaginationData()
    {
        return $this->pagination_data;
    }
}