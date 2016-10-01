<?php

namespace Model\Payment\Repositories;

use Dibi\Connection;
use Model\BaseTable;
use Model\Payment\Group;
use Model\Payment\GroupNotFoundException;

class GroupRepository implements IGroupRepository
{

	/** @var Connection */
	private $db;

	private $fields = [
		'id' => 'id',
		'type' => 'groupType',
		'unitId' => 'unitId',
		'eventId' => 'sisId',
		'name' => 'label',
		'defaultAmount' => 'amount',
		'dueDate' => 'maturity',
		'constantSymbol' => 'ks',
		'open' => 'open',
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
		$row = $this->db->select('*')
			->from(self::TABLE)
			->where('id = %i', $id)
			->fetch();

		if(!$row) {
			throw new GroupNotFoundException;
		}

	}

	public function save(Group $group)
	{
		// TODO: Implement save() method.
	}

	private function hydrate()
	{

	}

}
