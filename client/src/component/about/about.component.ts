import {Component, inject} from '@angular/core';
import {toSignal} from '@angular/core/rxjs-interop';
import {catchError, map, of} from 'rxjs';
import {TextBlockService} from '@app/service/text-block.service';

@Component({
  selector: 'app-about',
  standalone: true,
  templateUrl: './about.component.html',
  styleUrl: './about.component.css'
})
export class AboutComponent {
  /** Paragraphs of the "AI in My Workflow" section, edited from the admin API. Empty hides the section. */
  protected readonly aiWorkflow = toSignal(
    inject(TextBlockService).getBody('ai-workflow').pipe(
      map(body => body.split(/\n\s*\n/).map(p => p.trim()).filter(p => p !== '')),
      catchError(() => of([] as string[]))
    ),
    {initialValue: [] as string[]}
  );
}
