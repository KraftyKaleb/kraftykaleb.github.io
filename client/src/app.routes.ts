import {Routes} from '@angular/router';
import {HomeComponent} from './component/home/home.component';
import {AboutComponent} from './component/about/about.component';
import {ProjectsComponent} from './component/projects/projects.component';
import {SkillsComponent} from './component/skills/skills.component';
import {ContactComponent} from './component/contact/contact.component';

export const routes: Routes = [
    {path: '', component: HomeComponent},
    {path: 'about', component: AboutComponent},
    {path: 'projects', component: ProjectsComponent},
    {path: 'skills', component: SkillsComponent},
    {path: 'contact', component: ContactComponent},
    {path: '**', redirectTo: 'home'}
];
