import {inject, Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {map, Observable} from 'rxjs';
import * as t from 'io-ts';
import {Category} from "@app/model/category";
import {Project} from "@app/model/project";
import {decode} from "@app/model/decode";

@Injectable({providedIn: 'root'})
export class ProjectService {
    private readonly http = inject(HttpClient);
    private readonly headers = {Accept: 'application/json'};

    /** All project categories, in display order. */
    public getCategories(): Observable<Category[]> {
        return this.http.get<unknown>('api/categories', {headers: this.headers}).pipe(
            map(json => decode(t.array(Category.codec), json).map(Category.fromJson))
        );
    }

    /** All projects, in display order. */
    public getProjects(): Observable<Project[]> {
        return this.http.get<unknown>('api/projects', {headers: this.headers}).pipe(
            map(json => decode(t.array(Project.codec), json).map(Project.fromJson))
        );
    }
}
