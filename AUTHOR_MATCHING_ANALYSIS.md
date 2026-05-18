# 📋 การวิเคราะห์ปัญหา: การ Match ผู้แต่งในโหมด Update

## 🔍 สรุปปัญหา

เมื่อ**แก้ไขข้อมูลงานวิจัย** (Edit Mode) ใน `managePublication.php` **ไม่สามารถ match ผู้แต่งกับ users ในระบบได้** แต่เพียงแค่ดึงชื่อผู้แต่งมาแสดง ซึ่งแตกต่างจาก**การเพิ่มข้อมูลใหม่ด้วย AI** (Add Mode with AI) ที่สามารถ match ผู้แต่งได้อย่างสมบูรณ์

---

## 📂 ไฟล์ที่เกี่ยวข้อง

### 1. View File
- **`app/Views/admin/publications/managePublication.php`** (3117 บรรทัด)
  - จัดการทั้ง Add และ Edit mode ด้วย Modal เดียวกัน

### 2. JavaScript Files
- **`public/assets/js/publication-ai.js`** (1552 บรรทัด)
  - มีฟังก์ชัน matching ครบถ้วน:
    - `searchAuthorByEmail(email)` - line 998
    - `searchAuthorByThaiName(nameInfo)` - line 1021
    - `searchAuthorByEnglishName(nameInfo)` - line 1054
    - `ensureAuthorSplitFields(author)` - line 1280
    - `matchAndFillAuthor(authorData, index)` - line 924
  - Export เป็น global functions (line 1214-1218)

- **`public/assets/js/author-search.js`** (751 บรรทัด)
  - จัดการ dropdown search ชื่อผู้แต่ง

- **`public/assets/js/email-autocomplete.js`** (410 บรรทัด)
  - จัดการ autocomplete อีเมล

---

## 🔄 เปรียบเทียบ: Add with AI vs Edit

### ✅ **Add Mode with AI** (ทำงานได้ถูกต้อง)

#### Location: `managePublication.php` บรรทัด 2083-2184
```javascript
async function fillAddFormWithAIData(data) {
    // Fill authors with matching from database
    if (data.authors && Array.isArray(data.authors) && data.authors.length > 0) {
        for (let i = 0; i < data.authors.length; i++) {
            const authorData = data.authors[i];
            
            // ✅ Step 1: Ensure split fields (แยกชื่อ-นามสกุล)
            const enrichedAuthor = typeof window.ensureAuthorSplitFields === 'function' 
                ? window.ensureAuthorSplitFields(authorData) 
                : authorData;
            
            let matchedAuthor = null;
            
            // ✅ Step 2: Search by email first
            const email = enrichedAuthor.email || enrichedAuthor.author_email || null;
            if (email && typeof window.searchAuthorByEmail === 'function') {
                matchedAuthor = await window.searchAuthorByEmail(email);
                if (matchedAuthor) console.log(`✓ Author ${i + 1} matched by email`);
            }
            
            // ✅ Step 3: Search by Thai name
            const thaiName = enrichedAuthor.name_th || enrichedAuthor.thai_name || null;
            if (!matchedAuthor && thaiName && typeof window.searchAuthorByThaiName === 'function') {
                let thaiFirst = enrichedAuthor.thai_first_name;
                let thaiLast = enrichedAuthor.thai_last_name;
                if (!thaiFirst && typeof window.splitNameParts === 'function') {
                    const parts = window.splitNameParts(thaiName);
                    thaiFirst = parts.firstName;
                    thaiLast = parts.lastName;
                }
                matchedAuthor = await window.searchAuthorByThaiName({
                    fullName: thaiName,
                    firstName: thaiFirst,
                    lastName: thaiLast
                });
                if (matchedAuthor) console.log(`✓ Author ${i + 1} matched by Thai name`);
            }
            
            // ✅ Step 4: Search by English name
            const engName = enrichedAuthor.name_en || enrichedAuthor.english_name || null;
            if (!matchedAuthor && engName && typeof window.searchAuthorByEnglishName === 'function') {
                let engFirst = enrichedAuthor.gf_name;
                let engLast = enrichedAuthor.gl_name;
                if (!engFirst && typeof window.splitNameParts === 'function') {
                    const parts = window.splitNameParts(engName);
                    engFirst = parts.firstName;
                    engLast = parts.lastName;
                }
                matchedAuthor = await window.searchAuthorByEnglishName({
                    fullName: engName,
                    firstName: engFirst,
                    lastName: engLast
                });
                if (matchedAuthor) console.log(`✓ Author ${i + 1} matched by English name`);
            }
            
            // ✅ Step 5: Merge matched data with AI data
            const finalAuthorData = matchedAuthor ? {
                ...enrichedAuthor,
                author_name: matchedAuthor.thai_name || matchedAuthor.english_name || enrichedAuthor.author_name,
                name_th: matchedAuthor.thai_name || enrichedAuthor.name_th,
                name_en: matchedAuthor.english_name || enrichedAuthor.name_en,
                author_email: matchedAuthor.email || enrichedAuthor.author_email,
                author_affiliation: matchedAuthor.affiliation || matchedAuthor.organization || enrichedAuthor.author_affiliation,
                user_uid: matchedAuthor.user_uid || matchedAuthor.uid || null,
                matched: true
            } : {
                ...enrichedAuthor,
                matched: false
            };
            
            // ✅ Step 6: Add author to form
            addAddAuthor(finalAuthorData);
        }
    }
}
```

**✨ ผลลัพธ์:**
- ผู้แต่งที่พบใน Database จะถูก highlight เป็นสีเขียว
- ข้อมูลจะถูก auto-fill ทั้ง ชื่อ, อีเมล, สังกัด
- มี badge แสดงสถานะ "Matched" หรือ "Not Found"
- มี `user_uid` ถูกบันทึกเพื่อ link กับ user ในระบบ

---

### ❌ **Edit Mode** (ทำงานไม่ถูกต้อง - ปัญหา)

#### Location: `managePublication.php` บรรทัด 1254-1322
```javascript
async function openAddModalForEdit(publication) {
    // Clear and add authors with email-based matching
    document.getElementById('add_authors_container').innerHTML = '';
    addAuthorsCount = 0;

    if (publication.authors && publication.authors.length > 0) {
        for (let i = 0; i < publication.authors.length; i++) {
            const author = publication.authors[i];
            const authorName = author.name || author.author_name || '';
            const authorEmail = author.email || '';
            
            // ❌ PROBLEM 1: ตรวจสอบแค่ user_id ที่มีอยู่แล้ว
            if (author.user_id) {
                console.log(`Author ${i + 1} already matched with user_id: ${author.user_id}`);
                addAddAuthor({
                    author_name: authorName,
                    author_email: authorEmail,
                    author_affiliation: author.affiliation || '',
                    corresponding: author.corresponding === '1' || author.corresponding === 1,
                    user_uid: author.user_id,
                    matched: true
                });
                continue;
            }
            
            // ❌ PROBLEM 2: พยายาม match เฉพาะ email เท่านั้น (ไม่มี Thai name / English name match)
            let matchedUser = null;
            if (authorEmail && typeof window.searchAuthorByEmail === 'function') {
                console.log(`Author ${i + 1}: Searching by email "${authorEmail}"...`);
                try {
                    matchedUser = await window.searchAuthorByEmail(authorEmail);
                    if (matchedUser) {
                        console.log(`Author ${i + 1}: Found match by email!`, matchedUser);
                    }
                } catch (err) {
                    console.warn(`Author ${i + 1}: Email search failed:`, err);
                }
            }
            
            if (matchedUser) {
                // ✓ Email matched - ส่วนนี้ OK
                const matchedName = matchedUser.thai_name || 
                    (matchedUser.gf_name && matchedUser.gl_name ? `${matchedUser.gf_name} ${matchedUser.gl_name}` : '') ||
                    authorName;
                
                addAddAuthor({
                    author_name: matchedName,
                    author_email: matchedUser.email || authorEmail,
                    author_affiliation: matchedUser.affiliation || author.affiliation || 'มหาวิทยาลัยราชภัฏอุตรดิตถ์',
                    corresponding: author.corresponding === '1' || author.corresponding === 1,
                    user_uid: matchedUser.uid || matchedUser.id || '',
                    matched: true
                });
            } else {
                // ❌ PROBLEM 3: ไม่ได้พยายาม match ด้วยชื่อ (Thai/English)
                console.log(`Author ${i + 1}: No email match, using original name "${authorName}"`);
                addAddAuthor({
                    author_name: authorName,
                    author_email: authorEmail,
                    author_affiliation: author.affiliation || '',
                    corresponding: author.corresponding === '1' || author.corresponding === 1,
                    user_uid: '',
                    matched: false
                });
            }
        }
    }
}
```

**❌ ผลลัพธ์:**
- ผู้แต่งที่**ไม่มีอีเมล**จะไม่ถูก match เลย แม้จะมีชื่อในระบบก็ตาม
- ผู้แต่งที่**มีอีเมลไม่ตรงกัน**แต่ชื่อตรงกันจะไม่ถูก match
- ข้อมูลจะแสดงแค่**ชื่อที่บันทึกไว้เดิม** โดยไม่มี auto-fill หรือ matching

---

## 🐛 สาเหตุของปัญหา

### 1. **ขาดการ Enrich ข้อมูลผู้แต่ง**
- ❌ ไม่มีการเรียก `ensureAuthorSplitFields()` เพื่อแยกชื่อ-นามสกุล
- ✅ AI Mode มีการ enrich data ก่อน match

### 2. **Match เฉพาะ Email เท่านั้น**
- ❌ ไม่มีการ match ด้วย `searchAuthorByThaiName()`
- ❌ ไม่มีการ match ด้วย `searchAuthorByEnglishName()`
- ✅ AI Mode match ทั้ง 3 วิธี: Email → Thai Name → English Name

### 3. **ข้อมูลผู้แต่งจาก API ไม่ครบถ้วน**
- API Endpoint: `/admin/publications/get/{id}`
- ข้อมูลที่ได้อาจมีแค่:
  ```json
  {
    "name": "ชื่อเต็ม",
    "email": "email@example.com",
    "affiliation": "..."
  }
  ```
- ❌ ไม่มี fields แยก: `thai_name`, `english_name`, `thai_first_name`, `thai_last_name`, `gf_name`, `gl_name`
- ✅ AI Mode มี fields เหล่านี้ครบถ้วน

---

## 💡 แนวทางแก้ไข

### ✅ **Solution 1: ปรับปรุง `openAddModalForEdit()` ให้ใช้ Matching Logic แบบเดียวกับ AI Mode**

```javascript
async function openAddModalForEdit(publication) {
    // ... existing code ...
    
    if (publication.authors && publication.authors.length > 0) {
        for (let i = 0; i < publication.authors.length; i++) {
            const author = publication.authors[i];
            const authorName = author.name || author.author_name || '';
            const authorEmail = author.email || '';
            
            // ❌ เก่า: ตรวจสอบแค่ user_id เดิม
            // ✅ ใหม่: ข้ามการ match ถ้ามี user_id แล้ว (Keep existing user link)
            if (author.user_id) {
                console.log(`Author ${i + 1} already matched with user_id: ${author.user_id}`);
                addAddAuthor({
                    author_name: authorName,
                    author_email: authorEmail,
                    author_affiliation: author.affiliation || '',
                    corresponding: author.corresponding === '1' || author.corresponding === 1,
                    user_uid: author.user_id,
                    matched: true
                });
                continue;
            }
            
            // ✅ ใหม่: Enrich author data (แยกชื่อ-นามสกุล)
            const enrichedAuthor = {
                author_name: authorName,
                name: authorName,
                author_email: authorEmail,
                email: authorEmail,
                author_affiliation: author.affiliation || '',
                affiliation: author.affiliation || '',
                corresponding: author.corresponding === '1' || author.corresponding === 1
            };
            
            // เรียกใช้ ensureAuthorSplitFields ถ้ามี
            const processedAuthor = typeof window.ensureAuthorSplitFields === 'function' 
                ? window.ensureAuthorSplitFields(enrichedAuthor) 
                : enrichedAuthor;
            
            let matchedUser = null;
            
            // ✅ Step 1: Search by email
            if (authorEmail && typeof window.searchAuthorByEmail === 'function') {
                console.log(`Author ${i + 1}: Searching by email "${authorEmail}"...`);
                try {
                    matchedUser = await window.searchAuthorByEmail(authorEmail);
                    if (matchedUser) {
                        console.log(`Author ${i + 1}: Found match by email!`, matchedUser);
                    }
                } catch (err) {
                    console.warn(`Author ${i + 1}: Email search failed:`, err);
                }
            }
            
            // ✅ Step 2: Search by Thai name (ถ้าไม่เจอจาก email)
            if (!matchedUser && authorName && typeof window.searchAuthorByThaiName === 'function') {
                console.log(`Author ${i + 1}: Searching by Thai name "${authorName}"...`);
                try {
                    // แยกชื่อ-นามสกุล
                    let thaiFirst = processedAuthor.thai_first_name;
                    let thaiLast = processedAuthor.thai_last_name;
                    
                    if (!thaiFirst && typeof window.splitNameParts === 'function') {
                        const parts = window.splitNameParts(authorName);
                        thaiFirst = parts.firstName;
                        thaiLast = parts.lastName;
                    }
                    
                    matchedUser = await window.searchAuthorByThaiName({
                        fullName: authorName,
                        firstName: thaiFirst,
                        lastName: thaiLast
                    });
                    
                    if (matchedUser) {
                        console.log(`Author ${i + 1}: Found match by Thai name!`, matchedUser);
                    }
                } catch (err) {
                    console.warn(`Author ${i + 1}: Thai name search failed:`, err);
                }
            }
            
            // ✅ Step 3: Search by English name (ถ้ายังไม่เจอ)
            if (!matchedUser && authorName && typeof window.searchAuthorByEnglishName === 'function') {
                console.log(`Author ${i + 1}: Searching by English name "${authorName}"...`);
                try {
                    // แยกชื่อ-นามสกุล
                    let engFirst = processedAuthor.gf_name;
                    let engLast = processedAuthor.gl_name;
                    
                    if (!engFirst && typeof window.splitNameParts === 'function') {
                        const parts = window.splitNameParts(authorName);
                        engFirst = parts.firstName;
                        engLast = parts.lastName;
                    }
                    
                    matchedUser = await window.searchAuthorByEnglishName({
                        fullName: authorName,
                        firstName: engFirst,
                        lastName: engLast
                    });
                    
                    if (matchedUser) {
                        console.log(`Author ${i + 1}: Found match by English name!`, matchedUser);
                    }
                } catch (err) {
                    console.warn(`Author ${i + 1}: English name search failed:`, err);
                }
            }
            
            // ✅ Step 4: Merge matched data
            if (matchedUser) {
                const matchedName = matchedUser.thai_name || 
                    (matchedUser.gf_name && matchedUser.gl_name ? `${matchedUser.gf_name} ${matchedUser.gl_name}` : '') ||
                    authorName;
                
                addAddAuthor({
                    author_name: matchedName,
                    author_email: matchedUser.email || authorEmail,
                    author_affiliation: matchedUser.affiliation || author.affiliation || 'มหาวิทยาลัยราชภัฏอุตรดิตถ์',
                    corresponding: author.corresponding === '1' || author.corresponding === 1,
                    user_uid: matchedUser.uid || matchedUser.id || '',
                    matched: true
                });
                console.log(`✓ Author ${i + 1} matched successfully`);
            } else {
                // ไม่พบใน database
                console.log(`Author ${i + 1}: No match found, using original data`);
                addAddAuthor({
                    author_name: authorName,
                    author_email: authorEmail,
                    author_affiliation: author.affiliation || '',
                    corresponding: author.corresponding === '1' || author.corresponding === 1,
                    user_uid: '',
                    matched: false
                });
            }
        }
    }
}
```

---

### ✅ **Solution 2: สร้าง Reusable Function สำหรับ Author Matching**

เพื่อหลีกเลี่ยงการ duplicate code สามารถสร้างฟังก์ชันกลางได้:

```javascript
/**
 * Match author with database using multiple strategies
 * @param {Object} authorData - Author data from form/API
 * @returns {Object|null} - Matched user or null
 */
async function matchAuthorWithDatabase(authorData) {
    console.log('Matching author:', authorData);
    
    // Enrich data
    const enriched = typeof window.ensureAuthorSplitFields === 'function' 
        ? window.ensureAuthorSplitFields(authorData) 
        : authorData;
    
    let matched = null;
    
    // Strategy 1: Email
    const email = enriched.email || enriched.author_email;
    if (email && typeof window.searchAuthorByEmail === 'function') {
        matched = await window.searchAuthorByEmail(email);
        if (matched) {
            console.log('✓ Matched by email');
            return matched;
        }
    }
    
    // Strategy 2: Thai name
    const thaiName = enriched.name_th || enriched.thai_name || enriched.author_name || enriched.name;
    if (thaiName && typeof window.searchAuthorByThaiName === 'function') {
        let first = enriched.thai_first_name;
        let last = enriched.thai_last_name;
        
        if (!first && typeof window.splitNameParts === 'function') {
            const parts = window.splitNameParts(thaiName);
            first = parts.firstName;
            last = parts.lastName;
        }
        
        matched = await window.searchAuthorByThaiName({
            fullName: thaiName,
            firstName: first,
            lastName: last
        });
        
        if (matched) {
            console.log('✓ Matched by Thai name');
            return matched;
        }
    }
    
    // Strategy 3: English name
    const engName = enriched.name_en || enriched.english_name || enriched.author_name || enriched.name;
    if (engName && typeof window.searchAuthorByEnglishName === 'function') {
        let first = enriched.gf_name;
        let last = enriched.gl_name;
        
        if (!first && typeof window.splitNameParts === 'function') {
            const parts = window.splitNameParts(engName);
            first = parts.firstName;
            last = parts.lastName;
        }
        
        matched = await window.searchAuthorByEnglishName({
            fullName: engName,
            firstName: first,
            lastName: last
        });
        
        if (matched) {
            console.log('✓ Matched by English name');
            return matched;
        }
    }
    
    console.log('✗ No match found');
    return null;
}
```

จากนั้นใช้ในทั้ง Edit Mode และ AI Mode:

```javascript
// ใน openAddModalForEdit()
const matchedUser = await matchAuthorWithDatabase({
    name: authorName,
    email: authorEmail,
    affiliation: author.affiliation
});

// ใน fillAddFormWithAIData()
const matchedAuthor = await matchAuthorWithDatabase(enrichedAuthor);
```

---

## 🧪 วิธีทดสอบ

### Test Case 1: ผู้แต่งมี Email ตรงกับในระบบ
```
Input: { name: "สมชาย ใจดี", email: "somchai@uru.ac.th" }
Expected: ✅ Match โดย email
```

### Test Case 2: ผู้แต่งไม่มี Email แต่ชื่อไทยตรงกับในระบบ
```
Input: { name: "สมหญิง รักดี", email: "" }
Expected: ✅ Match โดยชื่อไทย
```

### Test Case 3: ผู้แต่งมีชื่ออังกฤษตรงกับในระบบ
```
Input: { name: "John Smith", email: "" }
Expected: ✅ Match โดยชื่ออังกฤษ
```

### Test Case 4: ผู้แต่งไม่พบในระบบ
```
Input: { name: "ผู้แต่งใหม่", email: "new@example.com" }
Expected: ✅ แสดงเป็น "Not Found" พร้อม badge สีเหลือง
```

---

## 📋 Checklist การแก้ไข

- [ ] เพิ่ม `ensureAuthorSplitFields()` ใน Edit Mode
- [ ] เพิ่ม `searchAuthorByThaiName()` ใน Edit Mode
- [ ] เพิ่ม `searchAuthorByEnglishName()` ใน Edit Mode
- [ ] สร้างฟังก์ชัน `matchAuthorWithDatabase()` แบบ reusable
- [ ] ทดสอบ 4 Test Cases ข้างต้น
- [ ] เพิ่ม console.log เพื่อ debug
- [ ] เพิ่ม error handling สำหรับ network errors
- [ ] Update UI ให้แสดง matched/not matched status

---

## 🎯 สรุป

**ปัญหาหลัก:** Edit Mode match ผู้แต่งแค่ **Email เท่านั้น** ทำให้ผู้แต่งที่ไม่มี Email หรือ Email ไม่ตรงกันจะไม่ถูก match แม้จะมีชื่อในระบบ

**วิธีแก้:** ใช้ **3-Step Matching Strategy** เหมือน AI Mode:
1. ✅ Match by Email
2. ✅ Match by Thai Name (ถ้าไม่เจอจาก Email)
3. ✅ Match by English Name (ถ้ายังไม่เจอ)

**ผลลัพธ์ที่คาดหวัง:** Edit Mode จะสามารถ match ผู้แต่งได้ครบถ้วนเหมือนกับ AI Mode ทำให้ผู้ใช้เห็นข้อมูลผู้แต่งที่ complete และถูกต้อง
