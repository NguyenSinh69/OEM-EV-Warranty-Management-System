<?php
// Giả lập danh sách VIN hợp lệ
function isValidVin($vin) {
    $validVins = ['VIN123', 'VIN456', 'VIN789'];
    return in_array($vin, $validVins);
}
?>
<?php