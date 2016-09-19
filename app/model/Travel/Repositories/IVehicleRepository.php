<?php

namespace Model\Travel\Repositories;

use Model\Travel\Vehicle;
use Model\Travel\VehicleNotFoundException;

interface IVehicleRepository
{

	/**
	 * @param int $id
	 * @throws VehicleNotFoundException
	 * @return Vehicle
	 */
	public function get($id);

	/**
	 * @param $unitId
	 * @return Vehicle[]
	 */
	public function getAll($unitId);

	/**
	 * @param int $unitId
	 * @return array
	 */
	public function getPairs($unitId);

	/**
	 * @param Vehicle $vehicle
	 */
	public function save(Vehicle $vehicle);

	/**
	 * Removes vehicle with specified ID
	 * @param $vehicleId
	 * @return bool
	 */
	public function remove($vehicleId);

}
