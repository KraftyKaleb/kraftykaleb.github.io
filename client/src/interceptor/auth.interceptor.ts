import {HttpErrorResponse, HttpInterceptorFn} from '@angular/common/http';
import {inject} from '@angular/core';
import {Router} from '@angular/router';
import {catchError, throwError} from 'rxjs';
import {AuthService} from "@app/service/auth.service";

/** Sends the admin's bearer token with API requests, and signs them out when the API rejects it. */
export const authInterceptor: HttpInterceptorFn = (request, next) => {
    const auth = inject(AuthService);
    const router = inject(Router);
    const token = auth.token();
    if (!token || !request.url.startsWith('api/') || request.url === 'api/token') {
        return next(request);
    }

    return next(request.clone({setHeaders: {Authorization: `Bearer ${token.token}`}})).pipe(
        catchError((error: unknown) => {
            if (error instanceof HttpErrorResponse && error.status === 401) {
                auth.logout();
                void router.navigate(['/admin/login']);
            }
            return throwError(() => error);
        })
    );
};
