<?php

namespace Model\Payment\Repositories;

use Dibi\Connection;
use Dibi\Row;
use Model\BaseTable;
use Model\Hydrator;
use Model\Payment\Group;
use Model\Payment\GroupNotFoundException;

class GroupRepository implements IGroupRepository
{

	/** @var Connection */
	private $db;

	/** @var Hydrator */
	private $hydrator;

	private $fields = [
		'id' => 'id',
		'type' => 'groupType',
		'unitId' => 'unitId',
		'eventId' => 'sisId',
		'name' => 'label',
		'defaultAmount' => 'amount',
		'dueDate' => 'maturity',
		'constantSymbol' => 'ks',
		'state' => 'state',
		'createdAt' => 'created_at',
	];

	const TABLE = BaseTable::TABLE_PA_GROUP;

	/**
	 * GroupRepository constructor.
	 * @param Connection $db
	 */
	public function __construct(Connection $db)
	{
		$this->db = $db;
	}

	/**
	 * @param int $id
	 * @return Group
	 * @throws GroupNotFoundException
	 */
	public function find($id)
	{
		$row = $this->db->select(array_flip($this->fields))
			->from(self::TABLE)
			->where('id = %i', $id)
			->fetch();

		if(!$row) {
			throw new GroupNotFoundException;
		}
		return $this->getHydrator()->create($row->toArray());
	}

	public function save(Group $group)
	{
		// TODO: Implement save() method.
	}

	/**
	 * @return Hydrator
	 */
	private function getHydrator()
	{
		if(!$this->hydrator) {
			$this->hydrator = new Hydrator(Group::class, array_keys($this->fields));
		}
		return $this->hydrator;
	}

}
