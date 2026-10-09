import {inject} from '@angular/core';
import {CanActivateFn, Router} from '@angular/router';
import {AuthService} from "@app/service/auth.service";

/** Sends visitors who are not signed in to the admin login page. */
export const adminGuard: CanActivateFn = () =>
    inject(AuthService).token() !== null || inject(Router).createUrlTree(['/admin/login']);
