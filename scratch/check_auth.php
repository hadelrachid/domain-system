<?php echo json_decode(file_get_contents("config/plugins.json"), true)["auth"]["active"] ? "active" : "inactive";
