import {Routes} from '@angular/router';
import {adminGuard} from "@app/guard/admin.guard";
import {LoginComponent} from './login/login.component';
import {LayoutComponent} from './layout/layout.component';
import {ProjectsComponent} from './projects/projects.component';
import {ProjectFormComponent} from './project-form/project-form.component';
import {CategoriesComponent} from './categories/categories.component';
import {TagsComponent} from './tags/tags.component';

export const adminRoutes: Routes = [
    {path: 'login', component: LoginComponent},
    {
        path: '',
        component: LayoutComponent,
        canActivate: [adminGuard],
        children: [
            {path: '', pathMatch: 'full', redirectTo: 'projects'},
            {path: 'projects', component: ProjectsComponent},
            {path: 'projects/new', component: ProjectFormComponent},
            {path: 'projects/:id', component: ProjectFormComponent},
            {path: 'categories', component: CategoriesComponent},
            {path: 'tags', component: TagsComponent}
        ]
    }
];
