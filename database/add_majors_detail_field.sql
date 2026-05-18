-- Add majors_detail field to store multiple majors information as JSON
-- JSON format: [{"major_name": "วิชาเอกชื่อ1", "admission_count": 20}, ...]

ALTER TABLE `student_admission_forms` 
ADD COLUMN `majors_detail` TEXT NULL COMMENT 'รายละเอียดวิชาเอกแต่ละตัว (JSON: [{major_name, admission_count}])' 
AFTER `admission_plan_count`;
