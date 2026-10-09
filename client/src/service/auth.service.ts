import {inject, Injectable, signal} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {map, Observable, tap} from 'rxjs';
import {Token} from "@app/model/token";
import {decode} from "@app/model/decode";

/** Logs admins in with PUT /api/token and keeps their bearer token for later requests. */
@Injectable({providedIn: 'root'})
export class AuthService {
    private static readonly storageKey = 'admin.token';

    private readonly http = inject(HttpClient);
    private readonly current = signal<Token | null>(AuthService.restore());

    /** The signed-in admin's token, or null when nobody is signed in or it has expired. */
    public token(): Token | null {
        const token = this.current();
        return token && !token.expired ? token : null;
    }

    public login(username: string, password: string): Observable<Token> {
        return this.http.put<unknown>('api/token', {username, password}, {headers: {Accept: 'application/json'}}).pipe(
            map(json => Token.fromJson(decode(Token.codec, json))),
            tap(token => {
                localStorage.setItem(AuthService.storageKey, JSON.stringify(Token.codec.encode({
                    token: token.token,
                    expiresAt: token.expiresAt.toISOString(),
                    username: token.username
                })));
                this.current.set(token);
            })
        );
    }

    public logout(): void {
        localStorage.removeItem(AuthService.storageKey);
        this.current.set(null);
    }

    private static restore(): Token | null {
        try {
            const stored = localStorage.getItem(AuthService.storageKey);
            const token = stored ? Token.fromJson(decode(Token.codec, JSON.parse(stored))) : null;
            return token && !token.expired ? token : null;
        } catch {
            return null;
        }
    }
}
