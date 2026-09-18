<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Authenticate Admin with passcode/password.
     */
    public function login(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $storedHash = Setting::get('admin_password');

        // Verify password
        $isValid = false;
        if ($storedHash && Hash::check($request->password, $storedHash)) {
            $isValid = true;
        } elseif (!$storedHash && $request->password === 'DADDYKUYALL') {
            $isValid = true;
            Setting::set('admin_password', Hash::make('DADDYKUYALL'));
        }

        if (!$isValid) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'รหัสผ่านแอดมินไม่ถูกต้อง กรุณาลองใหม่',
                ], 422);
            }
            return back()->withErrors(['password' => 'รหัสผ่านแอดมินไม่ถูกต้อง']);
        }

        session(['is_admin' => true]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'เข้าสู่ระบบแอดมินเรียบร้อยแล้ว',
            ]);
        }

        return back()->with('success', 'เข้าสู่ระบบแอดมินเรียบร้อยแล้ว');
    }

    /**
     * Logout from Admin mode.
     */
    public function logout(Request $request)
    {
        session()->forget('is_admin');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'ออกจากระบบแอดมินแล้ว',
            ]);
        }

        return redirect()->route('auction.index')->with('success', 'ออกจากระบบแอดมินแล้ว');
    }

    /**
     * Change Admin password.
     */
    public function changePassword(Request $request)
    {
        if (!session('is_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'ต้องเข้าสู่ระบบแอดมินก่อนดำเนินการ',
            ], 403);
        }

        $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:4',
        ]);

        $storedHash = Setting::get('admin_password');
        if (!Hash::check($request->old_password, $storedHash)) {
            return response()->json([
                'success' => false,
                'message' => 'รหัสผ่านเดิมไม่ถูกต้อง',
            ], 422);
        }

        Setting::set('admin_password', Hash::make($request->new_password));

        return response()->json([
            'success' => true,
            'message' => 'เปลี่ยนรหัสผ่านแอดมินสำเร็จแล้ว',
        ]);
    }

    /**
     * Check current admin session status.
     */
    public function status()
    {
        return response()->json([
            'is_admin' => (bool) session('is_admin', false),
        ]);
    }
}
