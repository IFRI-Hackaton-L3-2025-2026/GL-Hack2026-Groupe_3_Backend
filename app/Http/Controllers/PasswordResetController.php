<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Mail\OtpMail;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Password Reset', description: 'Réinitialisation du mot de passe via OTP')]
class PasswordResetController extends Controller
{
    #[OA\Post(
        path: '/api/v1/forgot-password',
        summary: 'Demander un code OTP',
        tags: ['Password Reset'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'client@test.com')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Code OTP envoyé par email'),
            new OA\Response(response: 404, description: 'Email non trouvé'),
            new OA\Response(response: 422, description: 'Erreur de validation')
        ]
    )]
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        // Générer un OTP à 6 chiffres
        $otp = strval(rand(100000, 999999));

        // Supprimer les anciens OTP de cet email
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Stocker le nouvel OTP
        DB::table('password_reset_tokens')->insert([
            'email'      => $request->email,
            'token'      => Hash::make($otp),
            'created_at' => now(),
        ]);

        // Envoyer l'email
        Mail::to($request->email)->send(new OtpMail($otp, $user->fullname));

        return response()->json([
            'success' => true,
            'message' => 'Code OTP envoyé à votre adresse email. Valable 10 minutes.',
        ], 200);
    }

    #[OA\Post(
        path: '/api/v1/verify-otp',
        summary: 'Vérifier le code OTP',
        tags: ['Password Reset'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'otp'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'client@test.com'),
                    new OA\Property(property: 'otp', type: 'string', example: '123456')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OTP valide'),
            new OA\Response(response: 400, description: 'OTP invalide ou expiré'),
            new OA\Response(response: 422, description: 'Erreur de validation')
        ]
    )]
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp'   => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun code OTP trouvé pour cet email',
            ], 400);
        }

        // Vérifier expiration (10 minutes)
        if (now()->diffInMinutes($record->created_at) > 10) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json([
                'success' => false,
                'message' => 'Code OTP expiré. Veuillez en demander un nouveau.',
            ], 400);
        }

        // Vérifier le code
        if (!Hash::check($request->otp, $record->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Code OTP incorrect',
            ], 400);
        }

        // Générer un token de réinitialisation
        $resetToken = bin2hex(random_bytes(32));

        // Mettre à jour avec le token de réinitialisation
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->update(['token' => Hash::make($resetToken)]);

        return response()->json([
            'success'      => true,
            'message'      => 'Code OTP valide',
            'reset_token'  => $resetToken,
        ], 200);
    }

    #[OA\Post(
        path: '/api/v1/reset-password',
        summary: 'Réinitialiser le mot de passe',
        tags: ['Password Reset'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'reset_token', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'reset_token', type: 'string'),
                    new OA\Property(property: 'password', type: 'string', minLength: 6),
                    new OA\Property(property: 'password_confirmation', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Mot de passe réinitialisé avec succès'),
            new OA\Response(response: 400, description: 'Token invalide ou expiré'),
            new OA\Response(response: 422, description: 'Erreur de validation')
        ]
    )]
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'                 => 'required|email|exists:users,email',
            'reset_token'           => 'required|string',
            'password'              => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide',
            ], 400);
        }

        // Vérifier expiration (10 minutes)
        if (now()->diffInMinutes($record->created_at) > 10) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json([
                'success' => false,
                'message' => 'Token expiré. Veuillez recommencer la procédure.',
            ], 400);
        }

        // Vérifier le reset token
        if (!Hash::check($request->reset_token, $record->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide',
            ], 400);
        }

        // Mettre à jour le mot de passe
        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password),
        ]);

        // Supprimer le token utilisé
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.',
        ], 200);
    }
}