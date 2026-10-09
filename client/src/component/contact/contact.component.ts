import {Component, inject, signal} from '@angular/core';
import {HttpErrorResponse} from '@angular/common/http';
import {FormBuilder, ReactiveFormsModule, Validators} from '@angular/forms';
import {ContactService} from '@app/service/contact.service';

type Status = 'idle' | 'sending' | 'sent' | 'error';

@Component({
  selector: 'app-contact',
  standalone: true,
  imports: [ReactiveFormsModule],
  templateUrl: './contact.component.html',
  styleUrl: './contact.component.css'
})
export class ContactComponent {
  private readonly contact = inject(ContactService);

  protected readonly form = inject(FormBuilder).nonNullable.group({
    name: ['', [Validators.required, Validators.maxLength(100)]],
    email: ['', [Validators.required, Validators.email, Validators.maxLength(180)]],
    subject: ['', [Validators.maxLength(150)]],
    message: ['', [Validators.required, Validators.maxLength(5000)]],
  });

  protected readonly status = signal<Status>('idle');
  protected readonly error = signal('');

  protected submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.status.set('sending');
    this.contact.send(this.form.getRawValue()).subscribe({
      next: () => {
        this.status.set('sent');
        this.form.reset();
      },
      error: (response: HttpErrorResponse) => {
        this.status.set('error');
        this.error.set(response.status === 429
          ? 'You have sent a lot of messages recently. Please try again later.'
          : response.status === 422
            ? 'Please check your details and try again.'
            : 'Something went wrong sending your message. Please try again later.');
      },
    });
  }

  protected invalid(control: keyof typeof this.form.controls): boolean {
    const field = this.form.controls[control];
    return field.invalid && field.touched;
  }
}
