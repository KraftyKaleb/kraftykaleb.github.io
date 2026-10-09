import {inject, Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {map, Observable} from 'rxjs';

export interface ContactRequest {
    name: string;
    email: string;
    subject: string;
    message: string;
}

@Injectable({providedIn: 'root'})
export class ContactService {
    private readonly http = inject(HttpClient);

    /** Sends a contact form message. The server emails a confirmation to the sender. */
    public send(request: ContactRequest): Observable<void> {
        return this.http.post<unknown>('api/contact_messages', request, {headers: {Accept: 'application/json'}}).pipe(
            map(() => undefined)
        );
    }
}
