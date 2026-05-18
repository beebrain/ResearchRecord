# Thai Font Setup for PDF Generation

## การติดตั้งฟอนต์ไทยสำหรับ PDFMake

ระบบได้ติดตั้งฟอนต์ Sarabun สำหรับ PDFMake แล้ว โดยใช้ไฟล์ฟอนต์ที่มีอยู่ในโฟลเดอร์นี้

### ฟอนต์ที่มีอยู่แล้ว:

- `sarabun-400.ttf` - Sarabun Regular (ใช้สำหรับ normal และ italics)
- `sarabun-700.ttf` - Sarabun Bold (ใช้สำหรับ bold และ bolditalics)

### การทำงาน:

1. ระบบจะโหลดฟอนต์อัตโนมัติเมื่อสร้าง PDF ผ่านสคริปต์ `pdfmake-thai-fonts.js`
2. ฟอนต์จะถูกแปลงเป็น base64 และลงทะเบียนกับ PDFMake
3. เอกสาร PDF จะใช้ฟอนต์ `THSarabunNew` โดยอัตโนมัติ

### การใช้งาน:

```javascript
// โหลดฟอนต์ก่อนสร้าง PDF
await window.PDFMakeThaiFonts.load();

// ตรวจสอบว่าฟอนต์โหลดแล้วหรือยัง
if (window.PDFMakeThaiFonts.isLoaded()) {
    // สร้าง PDF ได้
}
```

### หมายเหตุ:

- ฟอนต์จะถูกโหลดอัตโนมัติเมื่อเรียกใช้ฟังก์ชัน `generateCurriculumReport()`
- หากโหลดฟอนต์ไม่สำเร็จ ระบบจะใช้ฟอนต์ Roboto แทน (รองรับ Unicode แต่การแสดงผลภาษาไทยอาจไม่สวยงาม)
- ไฟล์ฟอนต์จะถูกโหลดเป็น base64 และใช้ใน PDFMake

### การเพิ่มฟอนต์อื่น (ถ้าต้องการ):

หากต้องการใช้ฟอนต์ TH Sarabun New แทน Sarabun:

1. ดาวน์โหลดฟอนต์ TH Sarabun New จาก:
   - https://fonts.google.com/specimen/Sarabun
   - หรือ https://www.f0nt.com/release/th-sarabun-new/

2. วางไฟล์ฟอนต์ในโฟลเดอร์นี้ (`public/assets/fonts/`) โดยใช้ชื่อไฟล์:
   - `THSarabunNew-Regular.ttf` (สำหรับ normal)
   - `THSarabunNew-Bold.ttf` (สำหรับ bold)

3. แก้ไขไฟล์ `public/assets/js/pdfmake-thai-fonts.js` เพื่อใช้ฟอนต์ใหม่

