<?php

declare(strict_types=1);

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
		'skautisId' => 'sisId',
		'name' => 'label',
		'defaultAmount' => 'amount',
		'dueDate' => 'maturity',
		'constantSymbol' => 'ks',
		'state' => 'state',
		'createdAt' => 'created_at',
		'lastPairing' => 'last_pairing',
		'emailTemplate' => 'email_info',
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
	public function find(int $id) : Group
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

	public function save(Group $group) : void
	{
		$row = $this->toRow($group);
		$id = $row['id'];
		unset($row['id']);

		if($id) {
			$this->db->update(self::TABLE, $row)
				->where('id = %i', $id)
				->execute();
		} else {
			$id = $this->db->insert(self::TABLE, $row)
				->execute(\dibi::IDENTIFIER);
			$this->getHydrator()->setProperty($group, 'id', $id);
		}
	}

	/**
	 * @param Group $group
	 * @return array
	 */
	private function toRow(Group $group) : array
	{
		$properties = $this->getHydrator()->toArray($group);
		$row = [];
		foreach($properties as $name => $value) {
			// Map property name to column name
			$row[$this->fields[$name]] = $value;
		}
		return $row;
	}

	/**
	 * @return Hydrator
	 */
	private function getHydrator() : Hydrator
	{
		if(!$this->hydrator) {
			$this->hydrator = new Hydrator(Group::class, array_keys($this->fields));
		}
		return $this->hydrator;
	}

}
