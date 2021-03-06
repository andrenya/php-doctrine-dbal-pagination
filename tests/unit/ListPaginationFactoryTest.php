<?php

namespace Ifedko\DoctrineDbalPagination\Test;

use Ifedko\DoctrineDbalPagination\SortingInterface;
use Mockery;
use Ifedko\DoctrineDbalPagination\ListPaginationFactory;

use Ifedko\DoctrineDbalPagination\Filter\Base\GreaterThanOrEqualFilter;
use Ifedko\DoctrineDbalPagination\Filter\Base\LessThanOrEqualFilter;
use Ifedko\DoctrineDbalPagination\Filter\FilterInterface;
use Ifedko\DoctrineDbalPagination\ListBuilder;
use Ifedko\DoctrineDbalPagination\Filter\Base\EqualFilter;

class TestListBuilder extends ListBuilder
{
    public $testSortingModel;

    /**
     * {@inheritDoc}
     */
    protected function configureSorting($parameters)
    {
        $sorting = [];
        $direction = (!empty($parameters['sortOrder'])) ? $parameters['sortOrder'] : 'asc';
        if (isset($parameters['sortBy']) && strlen($parameters['sortBy']) > 0) {
            $sorting[$parameters['sortBy']] = $direction;
        }

        if ($this->testSortingModel) {
            $sorting[] = $this->testSortingModel;
        }

        $this->sortings = $sorting;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function configureFilters($parameters)
    {
        $mapAvailableFilterByParameter = [
            'user_id' => new EqualFilter('id', \PDO::PARAM_INT),
            'name' => new EqualFilter('name', \PDO::PARAM_STR),
            'created_at_from' => new GreaterThanOrEqualFilter('user.created_at'),
            'created_at_to' => new LessThanOrEqualFilter('user.created_at')
        ];

        /* @var $filter FilterInterface */
        foreach ($mapAvailableFilterByParameter as $parameterName => $filter) {
            if (isset($parameters[$parameterName])) {
                $filter->bindValues($parameters[$parameterName]);
                $this->filters[] = $filter;
            }
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function baseQuery()
    {
        $builder = $this->getQueryBuilder();
        $builder
            ->select('id', 'name', 'created_at')
            ->from('users')
        ;
        return $builder;
    }
}

class ListPaginationFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateByLogIOSListBuilderTypeSuccess()
    {
        $dbConnectionMock = self::createDbConnectionMock();
        $listBuilderClassName = 'Ifedko\\DoctrineDbalPagination\\Test\\TestListBuilder';

        $listPagination = ListPaginationFactory::create($dbConnectionMock, $listBuilderClassName);

        $this->assertInstanceOf('Ifedko\\DoctrineDbalPagination\\ListPagination', $listPagination);
    }

    public function testCreateByLogIOSListBuilderTypeWithLowerCaseInTypeNameSuccess()
    {
        $dbConnectionMock = self::createDbConnectionMock();
        $listBuilderClassName = 'Ifedko\\DoctrineDbalPagination\\Test\\testListBuilder';

        $listPagination = ListPaginationFactory::create($dbConnectionMock, $listBuilderClassName);

        $this->assertInstanceOf('Ifedko\\DoctrineDbalPagination\\ListPagination', $listPagination);
    }

    public function testCreateIfUnknownListBuilderTypeThrowException()
    {
        $dbConnectionMock = self::createDbConnectionMock();
        $listBuilderClassName = 'Ifedko\\DoctrineDbalPagination\\Test\\NonExistingListBuilder';
        $expectedExceptionMessage = sprintf('Unknown list builder class %s', $listBuilderClassName);

        self::expectException('Ifedko\\DoctrineDbalPagination\\Exception\\ListPaginationFactoryException');
        self::expectExceptionMessage($expectedExceptionMessage);
        ListPaginationFactory::create($dbConnectionMock, $listBuilderClassName);
    }

    public function testSupportsComplexSorting()
    {
        $sortingModel = Mockery::mock(SortingInterface::class);
        $sortingModel->shouldReceive('apply')->once();

        $builder = new TestListBuilder(self::createDbConnectionMock());
        $builder->testSortingModel = $sortingModel;

        $builder->configure([]);
        $builder->query();
    }

    private static function createDbConnectionMock()
    {
        return Mockery::mock('\Doctrine\DBAL\Connection');
    }
}
