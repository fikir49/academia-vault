'use client';

import React, { useState, useRef } from 'react';

export default function RegisterWizard() {
  const [step, setStep] = useState(1);
  const [loading, setLoading] = useState(false);
  const videoRef = useRef(null);
  
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    password: '',
    university_id: '',
    department: '',
    sex: '',
    age: ''
  });

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  // Turn on the user's native device camera stream
  const startCamera = async () => {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 } });
      if (videoRef.current) {
        videoRef.current.srcObject = stream;
      }
    } catch (err) {
      alert("Camera access denied or unavailable. Biometric scanning requires active hardware validation.");
    }
  };

  // Handle phase transitions
  const goToBiometrics = () => {
    setStep(3);
    // Trigger camera activation immediately when step 3 loads
    setTimeout(startCamera, 100);
  };

  // Package everything and push to Laravel API
  const handleFinalSubmit = async () => {
    setLoading(true);

    // Simulate an extracted 468-dimensional float array from MediaPipe FaceMesh
    // (In our next frontend optimization pass, this will hook directly into the active WebGL Canvas)
    const mockBiometricMesh = Array.from({ length: 468 }, () => Math.random() * 2 - 1);

    const payload = {
      ...formData,
      age: parseInt(formData.age),
      biometric_mesh: mockBiometricMesh
    };

    try {
     // Make sure the entire suffix path is added to the end of the string!
const response = await fetch('https://fuzzy-spork-969pp59grp9qcpg4r-8000.app.github.dev/api/v1/auth/biometric-register', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      });

      const result = await response.json();

      if (response.ok) {
        alert(`Success! Account Secured.\nYour Minted Passport ID is: ${result.data.vault_id}`);
        // Kill the active webcam stream to release hardware resources safely
        if (videoRef.current && videoRef.current.srcObject) {
          videoRef.current.srcObject.getTracks().forEach(track => track.stop());
        }
        setStep(1); // Reset wizard
      } else {
  // Extract and compile the specific validation errors from Laravel
  if (result.errors) {
    const errorMessages = Object.values(result.errors).flat().join('\n');
    alert(`Security Validation Failed:\n${errorMessages}`);
  } else {
    alert(`Security Error: ${result.message || 'Registration rejected.'}`);
  }
}
    } catch (error) {
      alert("Network transport layer failure. Ensure your Laravel server is active on port 8000.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center p-4">
      <div className="w-full max-w-xl bg-slate-900/60 backdrop-blur-md border border-slate-800 rounded-2xl p-8 shadow-xl">
        
        {/* Progress Matrix Tracking Line */}
        <div className="mb-8">
          <div className="flex justify-between text-xs text-slate-400 mb-2">
            <span>STEP {step} OF 3</span>
            <span>{step === 1 ? 'Personal Identity' : step === 2 ? 'Academic Routing' : 'Biometric Security'}</span>
          </div>
          <div className="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
            <div className="h-full bg-blue-600 transition-all duration-300" style={{ width: `${(step / 3) * 100}%` }} />
          </div>
        </div>

        {/* Step 1: Core Personal Account Fields */}
        {step === 1 && (
          <div className="space-y-4">
            <h2 className="text-xl font-semibold text-white">Create Your Identity Profile</h2>
            <div>
              <label className="block text-sm text-slate-400 mb-1">Full Name</label>
              <input type="text" name="name" value={formData.name} onChange={handleInputChange} className="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-blue-500" placeholder="Enter your full name" />
            </div>
            <div>
              <label className="block text-sm text-slate-400 mb-1">Email Address</label>
              <input type="email" name="email" value={formData.email} onChange={handleInputChange} className="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-blue-500" placeholder="name@bdu.edu.et" />
            </div>
            <div>
              <label className="block text-sm text-slate-400 mb-1">Secure Password</label>
              <input type="password" name="password" value={formData.password} onChange={handleInputChange} className="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-blue-500" placeholder="••••••••" />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm text-slate-400 mb-1">Sex</label>
                <select name="sex" value={formData.sex} onChange={handleInputChange} className="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-blue-500">
                  <option value="">Select</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                </select>
              </div>
              <div>
                <label className="block text-sm text-slate-400 mb-1">Age</label>
                <input type="number" name="age" value={formData.age} onChange={handleInputChange} className="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-blue-500" />
              </div>
            </div>
            <button onClick={() => setStep(2)} className="w-full mt-4 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition-colors">Continue to Academic Details</button>
          </div>
        )}

        {/* Step 2: Academic Affiliation Fields */}
        {step === 2 && (
          <div className="space-y-4">
            <h2 className="text-xl font-semibold text-white">Academic Verification</h2>
            <div>
              <label className="block text-sm text-slate-400 mb-1">University ID Number</label>
              <input type="text" name="university_id" value={formData.university_id} onChange={handleInputChange} className="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-blue-500" placeholder="e.g. BDU123456" />
            </div>
            <div>
              <label className="block text-sm text-slate-400 mb-1">Department</label>
              <select name="department" value={formData.department} onChange={handleInputChange} className="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-blue-500">
                <option value="">Select Department</option>
                <option value="IS">Information Systems (IS)</option>
                <option value="CS">Computer Science (CS)</option>
              </select>
            </div>
            <div className="flex gap-4 mt-6">
              <button onClick={() => setStep(1)} className="w-1/3 border border-slate-800 hover:bg-slate-800 text-slate-300 py-2.5 rounded-lg transition-colors">Back</button>
              <button onClick={goToBiometrics} className="w-2/3 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition-colors">Proceed to Biometrics</button>
            </div>
          </div>
        )}

        {/* Step 3: Biometric Camera Verification View */}
        {step === 3 && (
          <div className="space-y-4 text-center">
            <h2 className="text-xl font-semibold text-white text-left">Biometric Vault Registration</h2>
            <p className="text-sm text-slate-400 text-left">Position your face clearly inside the camera boundary viewport framework to extract mathematical geometric nodes.</p>
            
            <div className="w-full h-64 bg-slate-950 border border-slate-800 rounded-xl flex items-center justify-center my-4 relative overflow-hidden">
              <video ref={videoRef} autoPlay playsInline muted className="w-full h-full object-cover transform -scale-x-100" />
              <div className="absolute inset-0 border-2 border-blue-500/30 rounded-xl pointer-events-none scale-90 border-dashed animate-pulse" />
            </div>

            <div className="flex gap-4 mt-6">
              <button onClick={() => setStep(2)} className="w-1/3 border border-slate-800 hover:bg-slate-800 text-slate-300 py-2.5 rounded-lg transition-colors" disabled={loading}>Back</button>
              <button onClick={handleFinalSubmit} className="w-2/3 bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2.5 rounded-lg transition-colors flex items-center justify-center gap-2" disabled={loading}>
                {loading ? "Minting Vault Passport..." : "Mint My Vault Passport"}
              </button>
            </div>
          </div>
        )}

      </div>
    </div>
  );
}