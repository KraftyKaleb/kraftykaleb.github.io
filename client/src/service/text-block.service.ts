import {inject, Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {map, Observable} from 'rxjs';

@Injectable({providedIn: 'root'})
export class TextBlockService {
    private readonly http = inject(HttpClient);

    /** The body of the text block with this slug, as plain text. */
    public getBody(slug: string): Observable<string> {
        return this.http.get<{body: unknown}>(`api/text_blocks/${encodeURIComponent(slug)}`, {headers: {Accept: 'application/json'}}).pipe(
            map(block => {
                if (typeof block?.body !== 'string') {
                    throw new TypeError(`Text block "${slug}" has no body`);
                }
                return block.body;
            })
        );
    }
}
