<?php
// scripts/test_claim.php
// Quick manual test for App\Models\Claim: create -> find -> update -> delete

require __DIR__ . '/../vendor/autoload.php';

// Load .env if present
if (file_exists(__DIR__ . '/../.env')) {
	$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
	$dotenv->load();
}

use App\Models\Claim;

function rr(string $label, $v): void
{
	echo "=== $label ===\n";
	print_r($v);
	echo "\n";
}

try {
	// create
	$data = [
		'vin' => 'TESTVIN' . substr(uniqid(), -6),
		'customer_id' => 1,
		'status' => 'PENDING',
		'description' => 'Test claim from script'
	];

	$created = Claim::create($data);
	rr('created', $created);

	if (!$created || !isset($created['id'])) {
		throw new Exception('Create did not return id');
	}

	$id = $created['id'];

	// find
	$found = Claim::find($id);
	rr('found', $found);

	// update
	$updated = Claim::update($id, ['status' => 'IN_PROGRESS', 'description' => 'Updated by test']);
	rr('updated', $updated);

	// delete
	$deleted = Claim::delete($id);
	rr('deleted (bool)', $deleted);

	$after = Claim::find($id);
	rr('after delete (should be null)', $after);

	echo "All done.\n";
} catch (Throwable $e) {
	echo "Error: " . $e->getMessage() . "\n";
	echo $e->getTraceAsString() . "\n";
}

