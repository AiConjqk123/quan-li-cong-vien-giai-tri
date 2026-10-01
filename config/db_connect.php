<?php
// Tắt hiển thị cảnh báo phụ, chỉ lấy lỗi kết nối
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$host = "127.0.0.1";
$user = "root";       
$password = "hoangvu123"; // Mật khẩu MySQL của bạn
$database = "amusement_park"; // Tên CSDL

// Khởi tạo kết nối
$conn = @new mysqli($host, $user, $password, $database);

// Nếu kết nối bị lỗi, in trực tiếp nguyên nhân ra màn hình
if ($conn->connect_error) {
    die("<h3 style='color:red;'>Kết nối CSDL thất bại!</h3>" .
        "<p><b>Nguyên nhân:</b> " . $conn->connect_error . "</p>" .
        "<p>Vui lòng kiểm tra lại <i>Mật khẩu</i> hoặc đảm bảo đã chạy lệnh tạo Database <b>amusement_park</b> trong MySQL Workbench!</p>");
}

$conn->set_charset("utf8mb4");
?>