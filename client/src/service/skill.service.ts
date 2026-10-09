import {inject, Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {map, Observable} from 'rxjs';
import * as t from 'io-ts';
import {Skill} from "@app/model/skill";
import {decode} from "@app/model/decode";

@Injectable({providedIn: 'root'})
export class SkillService {
    private readonly http = inject(HttpClient);
    private readonly headers = {Accept: 'application/json'};

    /** All skills, in display order. */
    public getSkills(): Observable<Skill[]> {
        return this.http.get<unknown>('api/skills', {headers: this.headers}).pipe(
            map(json => decode(t.array(Skill.codec), json).map(Skill.fromJson))
        );
    }
}
