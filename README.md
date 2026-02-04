# READ THE ENTIRE TEXT CAREFULLY – IT IS LONG

This project is valid for the **January / February / June / July / September 2026** exam sessions and can be carried out **individually or in pairs**.

## Classroom Agreement
Today, **LLMs** exist and are extremely helpful in programming. Each of us has a favorite LLM, a sort of trusted *“group partner”* to build things and fix bugs.  
For this project, you may use LLMs provided that:

1. You **declare their use**, and  
2. You are able to **explain and justify the adopted solutions** in the project (likely suggested by your “group partner”).

---

# Project

This year, the **SAW project** consists of developing a **(simple) online game**, referred to in this document as **GameSAW**.  
You choose the game, and as stated above, you may rely on your trusted LLM for its implementation.

There is also the **back-end** part of the application, which can be implemented using **one of the two approaches covered in class**:

- Generating **HTML markup embedded in PHP**
- Generating **JSON payloads** sent to the client, which renders the page using **JavaScript**

The **mandatory features** are the same for everyone and are marked with an `*`.

---

## 1) Mandatory Features (for everyone)

- GameSAW Homepage *
- New user registration *
- Login *
- User profile visualization *
- User profile editing *
- Game leaderboard *
- Logout *

---

## 2) Optional Features

- Site blog  
- Shopping cart for game gadgets  
- Rating system  
- Badges  
- Internal chat  
- Administration area  
- Newsletter  
- Right to be forgotten (GDPR)  
- Other features to be agreed upon together  

---

## 3) Technical Requirements

## 4) Submission Instructions

---

# Mandatory Features (Detailed Description)

## GameSAW Homepage *
Every website has a public section, and GameSAW must also have its own *“showcase”* to introduce itself to users (what we do, who we are, where we are, contacts, etc.).  
The first mandatory feature requires the creation of the **GameSAW homepage**.

**Note:** You do not need to implement all public pages. You may also create links pointing to pages that simply display the text *“under construction”*.

---

## New User Registration *
Users can register via a dedicated form where they must provide:

- An **email address** (unique identifier)
- **First name** and **last name**
- A **password**, entered twice for confirmation

If you choose to use **Google authentication**, you may skip this form, but you must still **store the user data received from Google** in the users table of the database.

Users may have a **profile** including, for example:

- City of residence  
- A short self-description (*“About me”*)  
- Links to a personal website and/or social media pages  

All profile information is **optional** and can be requested later, after registration/authentication.

If useful for GameSAW, you may also ask users to upload a **profile picture**.  
Be careful with directory permissions: the image folder must be writable by the web server.

Since this is a game, you may also define a **user level** (e.g., Beginner, Intermediate, Expert).

---

## Login *
Some operations require authentication. Once logged in, the user accesses a **restricted area** of the site.

You can implement:

- A traditional login form, or  
- **Google authentication**

Authenticated users can be managed using:

- **PHP sessions**, or  
- **JWT tokens**

---

## User Profile Visualization *
Each user must be able to view their profile and see the information stored in the database.

---

## User Profile Editing *
Each user must be able to edit their profile.  
Fields must be **pre-filled** with existing data.

**Important:**  
The **password hash must never be shown**.  
It is recommended to separate password editing from profile editing.

---

## Game Leaderboard *
GameSAW must display a **leaderboard**, possibly limited to the **top 10** players.

If your game has no score system, assign users a **random integer score**.

---

## Logout *
Users must be able to log out.

On logout:
- Close the session  
- Delete the session cookie  
- Delete the *Remember Me* cookie (if implemented)

If using JWTs, see:  
*How to Invalidate a JWT Token After Logout: Risks & Solutions*

---

# Optional Features  
**[1 feature for individual projects, 3 for pair projects]**

## Site Blog
A blog can be added to keep users engaged.  
JS editors such as **TinyMCE** may be used.

Posts may be restricted to admin/editor roles, while comments are open to users, possibly with moderation.

---

## Shopping Cart for Game Gadgets
A shopping cart may be implemented (payment not required).

Users can:
- Add products  
- Remove products  

At checkout, the cart is emptied without payment.

Purchase history tracking is recommended.

Possible implementations:
- Database  
- PHP sessions  
- Browser Local Storage  

---

## Rating System
Products (or the game itself) may be rated using a star-based system.

Ratings must be allowed only for:
- Authenticated users  
- Users who have purchased the product  

If no products exist, users may rate the game only if they have played at least once.

---

## Badges
Users may earn **badges** based on their game score.

Badges can be displayed in:
- User profile  
- Leaderboard  

---

## Internal Chat
Registered users can exchange short messages.

Requirements:
- Messages stored in the database  
- No email notifications required  

Possible layouts:
- Chat-style (WhatsApp / Telegram)  
- Forum-style threads  

---

## Administration Area
Accessible only to admin users.

Possible features:
- View users  
- View products  
- View blog posts  
- Ban users (without deleting them)

A complete admin panel is **not required**.

---

## Newsletter
Admins may send newsletters using **PHPMailer**.

Users usually subscribe during registration.  
If non-registered subscriptions are allowed, email verification is required.

---

## Right to Be Forgotten (GDPR)
Users must be able to request **permanent deletion** of their personal data in compliance with GDPR.

---

# Technical Requirements
Use the technologies covered in class:

- HTML5 and CSS (including libraries)
- JavaScript (including libraries)
- PHP, sessions, and database access

### Debugging

**JavaScript:** browser console  
**PHP:** check `display_errors` in `php.ini`  
**SQL:** print queries and test them in phpMyAdmin  
**Redirects:** comment out redirects to debug hidden errors

---

# Submission Instructions
The project must be uploaded to the **course server** **one day before** the oral discussion.

Pair projects must be discussed **together on the same day**, except for exceptional cases.

---

## Good luck and happy coding! 🚀
