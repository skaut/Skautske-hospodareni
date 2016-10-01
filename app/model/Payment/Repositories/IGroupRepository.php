<?php

namespace Model\Payment\Repositories;

use Model\Payment\Group;
use Model\Payment\GroupNotFoundException;

interface IGroupRepository
{

	/**
	 * @param int $id
	 * @return Group
	 * @throws GroupNotFoundException
	 */
	public function find($id);

	/**
	 * @param Group $group
	 */
	public function save(Group $group);

}
