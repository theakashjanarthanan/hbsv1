<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Website Team</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
  <style>
    body { font-family: 'Segoe UI', sans-serif; background: #f9f9f9; }
    .team-section { padding: 100px 0 60px; } /* extra top padding for navbar */
    .section-title { margin-bottom: 40px; }
    .card {
      border-radius: 15px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      transition: transform 0.3s;
    }
    .card:hover {
      transform: translateY(-8px);
    }
    .card img {
      width: 120px;
      height: 120px;
      object-fit: cover;
      border-radius: 50%;
      margin: 20px auto 10px;
    }
    .card-title {
      font-weight: bold;
      margin-top: 10px;
    }
    .card-text {
      color: #555;
      font-size: 14px;
    }
    .navbar-logo img {
      height: 45px;
    }
    .navbar-title {
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
    }
    .navbar-title h4 {
      font-size: 20px;
      font-weight: 600;
      margin: 0;
    }

    .navbar {
        background-color: rgba(0, 123, 255, 0.9);
        height: 80px;   /* fixed height */
    }
    .navbar-logo img {
        height: 45px;
    }

    .guidance-card {
      border-radius: 20px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
      transition: all 0.3s ease;
      min-height: 280px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .guidance-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 35px rgba(0,0,0,0.2);
    }

    .guidance-icon {
      margin-bottom: 20px;
    }

    .guidance-section h3, .team-section h3 {
      position: relative;
      margin-bottom: 50px;
    }

    .guidance-section h3::after, .team-section h3::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 80px;
      height: 3px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 2px;
    }

    .profile-image-container {
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .profile-image {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #e0e0e0;
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      padding: 5px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
  </style>
</head>
<body>

<!-- Navbar  -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background-color: rgba(0, 123, 255, 0.9);">
  <div class="container-fluid d-flex align-items-center justify-content-between px-4">
      <div class="navbar-logo">
          <img src="image/logo/PU_Logo_Full.png" alt="Pondicherry University Logo">
      </div>
      <div class="navbar-title">
          <h4 class="text-white m-0">UNIVERSITY HALL BOOKING SYSTEM</h4>
      </div>
      <a href="index.php" class="btn btn-light btn-sm" style="display: inline-block; padding: 8px 10px; font-family: 'Arial', sans-serif; font-size: 16px; font-weight: bold; color: #333; background-color: #f8f9fa; border: 2px solid #ddd; border-radius: 8px; text-align: center; text-decoration: none; transition: all 0.3s ease-in-out; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
            Back to Login
      </a>
      
  </div>
</nav>

<!-- Contributors Section -->
<div class="container team-section">
  <h2 class="text-center section-title">Pondicherry University</h2>
  <h4 class="text-center section-title">Website Team</h4>
  <p class="text-center">
    We're a team of thinkers, builders, and collaborators who contributed to the Hall Booking System.
  </p>

  <!-- Project Core Team -->
  <div class="team-section mb-5" style="padding-top: 20px;">
    <h3 class="text-center mb-4" style="color: #2c3e50; font-weight: 600;">Project Core Team</h3>
    <div class="row justify-content-center">
      <div class="col-md-4 mb-4">
        <div class="card p-4 text-center guidance-card" style="background: white; color: #333; border: 1px solid #e0e0e0;">
          <div class="profile-image-container mb-3">
            <img src="image/male_profile.png" alt="Person 1" class="profile-image">
          </div>
          <h5 class="card-title" style="color: #333; font-weight: bold;">Person 1</h5>
          <p class="card-text" style="color: #666; font-size: 16px;">Database & Login System</p>
          <p class="card-text" style="color: #888; font-size: 14px;">Backend Developer</p>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="card p-4 text-center guidance-card" style="background: white; color: #333; border: 1px solid #e0e0e0;">
          <div class="profile-image-container mb-3">
            <img src="image/male_profile.png" alt="Person 2" class="profile-image">
          </div>
          <h5 class="card-title" style="color: #333; font-weight: bold;">Person 2</h5>
          <p class="card-text" style="color: #666; font-size: 16px;">Backend Developer</p>
          <p class="card-text" style="color: #888; font-size: 14px;">API Development</p>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="card p-4 text-center guidance-card" style="background: white; color: #333; border: 1px solid #e0e0e0;">
          <div class="profile-image-container mb-3">
            <img src="image/male_profile.png" alt="Person 3" class="profile-image">
          </div>
          <h5 class="card-title" style="color: #333; font-weight: bold;">Person 3</h5>
          <p class="card-text" style="color: #666; font-size: 16px;">UI/UX Designer</p>
          <p class="card-text" style="color: #888; font-size: 14px;">Frontend Design</p>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="card p-4 text-center guidance-card" style="background: white; color: #333; border: 1px solid #e0e0e0;">
          <div class="profile-image-container mb-3">
            <img src="image/male_profile.png" alt="Person 4" class="profile-image">
          </div>
          <h5 class="card-title" style="color: #333; font-weight: bold;">Person 4</h5>
          <p class="card-text" style="color: #666; font-size: 16px;">UI/UX Designer</p>
          <p class="card-text" style="color: #888; font-size: 14px;">Interface Design</p>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="card p-4 text-center guidance-card" style="background: white; color: #333; border: 1px solid #e0e0e0;">
          <div class="profile-image-container mb-3">
            <img src="image/male_profile.png" alt="Person 5" class="profile-image">
          </div>
          <h5 class="card-title" style="color: #333; font-weight: bold;">Person 5</h5>
          <p class="card-text" style="color: #666; font-size: 16px;">UI/UX Designer</p>
          <p class="card-text" style="color: #888; font-size: 14px;">Visual Design</p>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="card p-4 text-center guidance-card" style="background: white; color: #333; border: 1px solid #e0e0e0;">
          <div class="profile-image-container mb-3">
            <img src="image/male_profile.png" alt="Person 6" class="profile-image">
          </div>
          <h5 class="card-title" style="color: #333; font-weight: bold;">Person 6</h5>
          <p class="card-text" style="color: #666; font-size: 16px;">UI/UX Designer</p>
          <p class="card-text" style="color: #888; font-size: 14px;">Component Design</p>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="card p-4 text-center guidance-card" style="background: white; color: #333; border: 1px solid #e0e0e0;">
          <div class="profile-image-container mb-3">
            <img src="image/male_profile.png" alt="Person 7" class="profile-image">
          </div>
          <h5 class="card-title" style="color: #333; font-weight: bold;">Person 7</h5>
          <p class="card-text" style="color: #666; font-size: 16px;">UI/UX Designer</p>
          <p class="card-text" style="color: #888; font-size: 14px;">User Experience</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Under the Guidance of Section -->
  <div class="guidance-section mb-5" style="position: relative; bottom: 50px;">
    <h3 class="text-center mb-4" style="color: #2c3e50; font-weight: 600;">Under the Guidance of</h3>
    <div class="row justify-content-center">
      <div class="col-md-4 mb-4">
        <div class="card p-4 text-center guidance-card" style="background: white; color: #333; border: 1px solid #e0e0e0;">
          <h5 class="card-title" style="color: #333; font-weight: bold;">Dr.  </h5>
          <p class="card-text" style="color: #666; font-size: 16px;">Project Supervisor</p>
          <p class="card-text" style="color: #888; font-size: 14px;">Department of Computer Science</p>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="card p-4 text-center guidance-card" style="background: white; color: #333; border: 1px solid #e0e0e0;">
          <h5 class="card-title" style="color: #333; font-weight: bold;">Prof.</h5>
          <p class="card-text" style="color: #666; font-size: 16px;">Technical Advisor</p>
          <p class="card-text" style="color: #888; font-size: 14px;">Department of Information Technology</p>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="card p-4 text-center guidance-card" style="background: white; color: #333; border: 1px solid #e0e0e0;">
          <h5 class="card-title" style="color: #333; font-weight: bold;">Dr.</h5>
          <p class="card-text" style="color: #666; font-size: 16px;">Mentor</p>
          <p class="card-text" style="color: #888; font-size: 14px;">Department of Software Engineering</p>
        </div>
      </div>
    </div>
  </div>

</body>
</html>
<?php include 'assets/footer.php'; ?>
