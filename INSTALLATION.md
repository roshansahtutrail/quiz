# 🚀 INSTALLATION INSTRUCTIONS

## Quiz Management System v1.0

### ⏱️ Installation Time: 5 Minutes

---

## ✅ PREREQUISITES

Before starting, ensure you have:
- ✅ XAMPP installed (with PHP 8.1+ and MySQL 8+)
- ✅ Apache running
- ✅ MySQL running
- ✅ Admin access to computer
- ✅ All quiz-app files extracted

---

## 📋 STEP-BY-STEP INSTALLATION

### **STEP 1: Extract Project Files** (30 seconds)

1. Extract the `quizapp` folder
2. Copy to: `C:\xampp\htdocs\quizapp`
3. Verify folder exists at that location
4. You should see:
   - `index.php` in the folder
   - `admin/` folder
   - `quiz/` folder
   - `database/` folder
   - etc.

### **STEP 2: Start XAMPP Services** (1 minute)

1. Open XAMPP Control Panel from Start Menu
2. Click the "Start" button next to **Apache**
   - Wait for green indicator
   - Should show "Running"
3. Click the "Start" button next to **MySQL**
   - Wait for green indicator
   - Should show "Running"

✅ Both Apache and MySQL should now be running (green indicators)

### **STEP 3: Import Database** (2 minutes)

1. Open your browser
2. Visit: `http://localhost/phpmyadmin`
3. You should see phpMyAdmin login page

**If prompted to login:**
- Username: `root`
- Password: (leave empty)
- Click "Go"

4. On the left side, click on **"Import"** tab at the top
5. Under "Import files", click **"Choose File"**
6. Navigate to: `C:\xampp\htdocs\quizapp\database\quiz_app.sql`
7. Select the file and click "Open"
8. Scroll down and click **"Import"** button
9. You should see: **"Import has been successfully finished, 11 table(s) have been created."**

✅ Database imported successfully!

### **STEP 4: Verify Installation** (1 minute)

1. Open browser and go to: `http://localhost/quizapp/verify-installation.php`
2. System will display installation check results
3. You should see green checkmarks (✓ PASS) for:
   - ✅ PHP Version
   - ✅ PDO Extension
   - ✅ All Directories
   - ✅ All Configuration Files
   - ✅ Database Connection
   - ✅ All 11 Tables

If all checks pass, continue to **STEP 5**.

⚠️ If any checks fail:
- Review the error message
- Check the troubleshooting section below
- Ensure MySQL and Apache are running
- Verify database was imported

### **STEP 5: Access the System** (30 seconds)

1. Open browser
2. Go to: `http://localhost/quizapp`
3. You should see home page with two buttons:
   - "Admin Login"
   - "Team Login"

✅ Installation complete!

---

## 🔐 FIRST TIME LOGIN

### Admin Login

1. Click "Admin Login" button from home page
2. Or visit: `http://localhost/quizapp/admin/login.php`
3. Enter credentials:
   - **Username:** `admin`
   - **Password:** `password`
4. Click "Login"
5. You should see the Admin Dashboard with statistics

⚠️ **IMPORTANT:** Change the admin password immediately after first login!

### Team Login

1. Click "Team Login" button from home page
2. Or visit: `http://localhost/quizapp/quiz/login.php`
3. Enter sample credentials:
   - **Username:** `team_alpha` (or any sample team)
   - **Password:** `password`
4. Click "Join Quiz"
5. You should see quiz panel (waiting for round)

---

## ⚙️ POST-INSTALLATION SETUP

After successful installation, follow these steps:

### 1. Change Admin Password

1. Login as admin
2. Navigate to **Settings** (Coming Soon)
3. Or manually create a new admin via database

**For now:** Login to phpMyAdmin → admins table → Edit admin → Change password using bcrypt

### 2. Create Your Quiz Competition

1. **Create Rounds:** Admin → Rounds → Add Round
2. **Add Questions:** Admin → Questions → Select Round → Add Question
3. **Create Teams:** Admin → Teams → Add Team
4. **Activate Round:** Admin → Rounds → Click "Activate"
5. Teams can now login and take the quiz!

### 3. Monitor Progress

1. Visit **Admin → Leaderboard**
2. See real-time scores as teams submit
3. View detailed results for each team

---

## 🧪 TEST THE SYSTEM

### Quick Test Workflow

1. **Login as Admin:** Use admin/password
2. **Activate First Round:** Admin → Rounds → Activate
3. **Login as Team:** Use team_alpha/password
4. **Take Quiz:** Answer questions
5. **View Results:** See score after submission
6. **Check Leaderboard:** Admin → Leaderboard → See team score

---

## 🛠️ TROUBLESHOOTING

### Problem: Apache Won't Start

**Solution:**
1. Check if port 80 is in use
2. Try clicking "Config" → "Apache (httpd.conf)"
3. Look for errors in XAMPP logs
4. Restart computer if necessary

### Problem: MySQL Won't Start

**Solution:**
1. Check if MySQL is already running
2. Stop and restart XAMPP
3. Check if port 3306 is in use
4. Verify MySQL datafiles are not corrupted

### Problem: 404 Error When Visiting Website

**Solution:**
1. Verify folder is in `C:\xampp\htdocs\quizapp`
2. Ensure folder name is exactly `quizapp` (case-sensitive)
3. Try: `http://localhost/quizapp/index.php`
4. Clear browser cache (Ctrl+Shift+Delete)

### Problem: phpMyAdmin Not Loading

**Solution:**
1. Verify Apache is running (green indicator)
2. Try: `http://localhost`
3. Look for phpMyAdmin link on XAMPP page
4. Default login: root / (empty password)

### Problem: Database Import Failed

**Solution:**
1. Check file size: `database/quiz_app.sql` (should be ~50KB)
2. Verify phpMyAdmin is responsive
3. Try importing again
4. Check for error messages in phpMyAdmin

### Problem: "Connection Refused" When Logging In

**Solution:**
1. Verify MySQL is running
2. Check credentials in `includes/config.php`
3. Verify database `quiz_app` exists in phpMyAdmin
4. Check database tables exist (11 tables)

### Problem: Blank White Page

**Solution:**
1. Press F12 to open developer console
2. Check "Console" tab for errors
3. Check "Network" tab for failed requests
4. Review logs: `logs/YYYY-MM-DD.log`
5. Verify `includes/config.php` file exists

### Problem: Questions Don't Display in Quiz

**Solution:**
1. Verify questions were added to the round
2. Check if round is activated
3. Verify question sequence is set (1, 2, 3...)
4. Check browser console (F12) for AJAX errors

### Problem: Leaderboard Shows No Teams

**Solution:**
1. Verify at least one team submitted answers
2. Check if round results were saved
3. Verify team login credentials are correct
4. Ensure team took the active round quiz

---

## 🔍 VERIFICATION CHECKLIST

- [ ] XAMPP Control Panel installed
- [ ] Apache started (green indicator)
- [ ] MySQL started (green indicator)
- [ ] quizapp folder in `C:\xampp\htdocs\quizapp`
- [ ] Accessed `http://localhost/phpmyadmin`
- [ ] Imported `quiz_app.sql` successfully
- [ ] Verification script passed all checks
- [ ] Can access `http://localhost/quizapp`
- [ ] Can login as admin
- [ ] Can see dashboard
- [ ] Can activate round
- [ ] Can login as team
- [ ] Can take quiz
- [ ] Can see results

---

## 📱 SYSTEM REQUIREMENTS

### Minimum
- Windows Vista or later
- 500MB free disk space
- 2GB RAM
- Internet connection (for CDN resources)

### Recommended
- Windows 10 or 11
- 1GB free disk space
- 4GB RAM
- Broadband internet

### Software
- XAMPP 7.4+ (with PHP 8.1+, MySQL 8+)
- Modern web browser (Chrome, Firefox, Safari, Edge)

---

## 📝 IMPORTANT NOTES

1. **Default Password:** Always change `admin`'s password on first login
2. **File Permissions:** Ensure `uploads/`, `logs/`, `backups/` are writable
3. **Browser Cache:** Clear cache if pages don't load
4. **MySQL Restart:** Always restart MySQL if computer restarts
5. **Backup:** Create backup before making changes
6. **Logs:** Check `logs/` folder for error messages
7. **Sample Data:** Remove sample data before production use

---

## 🎓 NEXT STEPS

After successful installation:

1. **Read Documentation**
   - Open `README.md` for full guide
   - Read `QUICKSTART.md` for quick setup

2. **Setup Your Competition**
   - Create rounds with names
   - Add questions to each round
   - Create team accounts

3. **Run Competition**
   - Activate first round
   - Teams login and take quiz
   - Monitor leaderboard
   - View results

4. **Customize**
   - Change theme color
   - Update application name
   - Add school logo
   - Adjust question times

---

## 📞 SUPPORT

### If Installation Fails:

1. **Check Logs:**
   - Open `logs/` folder
   - Look for error messages
   - Review PHP error log in XAMPP

2. **Verify Setup:**
   - Run `verify-installation.php`
   - Check all items pass

3. **Check Configuration:**
   - Open `includes/config.php`
   - Verify all constants are correct
   - Ensure database credentials match

4. **Review Database:**
   - Open phpMyAdmin
   - Verify `quiz_app` database exists
   - Verify all 11 tables are present
   - Check sample data is loaded

---

## ✅ INSTALLATION COMPLETE!

Once you see this message on the verification page:

```
╔════════════════════════════════════════════════════════════╗
║  ✓ ALL CHECKS PASSED - SYSTEM IS READY!                   ║
╚════════════════════════════════════════════════════════════╝
```

**You're ready to use the Quiz Management System!**

### Quick Links After Installation:
- 🏠 **Home:** `http://localhost/quizapp`
- 👨‍💼 **Admin:** `http://localhost/quizapp/admin/login.php`
- 🎯 **Teams:** `http://localhost/quizapp/quiz/login.php`
- 📊 **Database:** `http://localhost/phpmyadmin`

---

## 🎉 CONGRATULATIONS!

Your Quiz Management System is installed and ready for use!

Happy quizzing! 📚✨

---

**Version:** 1.0.0  
**Last Updated:** June 23, 2026  
**Support:** Refer to README.md for comprehensive documentation
