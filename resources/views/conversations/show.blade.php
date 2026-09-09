@extends('layout.master')

@section('title', 'Conversation Details')

@push('plugin-styles')
  <style>
    .chat-timeline { max-height: 400px; overflow-y: auto; padding: 15px; border-radius: 8px; border: 1px solid #e9ecef; background: #fff; }
    .chat-bubble { max-width: 80%; padding: 10px 15px; border-radius: 12px; margin-bottom: 15px; position: relative; }
    .chat-inbound { background: #f1f3f5; color: #333; float: left; clear: both; border-top-left-radius: 0; }
    .chat-outbound { background: #00c853; color: #fff; float: right; clear: both; border-top-right-radius: 0; }
    .chat-time { font-size: 0.70rem; opacity: 0.8; margin-top: 5px; text-align: right; }
    .chat-input-area { border: 1px solid #e9ecef; border-radius: 20px; padding: 5px 15px; display: flex; align-items: center; }
    .chat-input { border: none; flex-grow: 1; outline: none; box-shadow: none; background: transparent; }

    
    .section-title { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #6c757d; margin-bottom: 1rem; letter-spacing: 0.5px; }
    .stat-card { border: 1px solid #e9ecef; border-radius: 8px; padding: 15px; text-align: center; }
    .stat-value { font-size: 2rem; font-weight: 700; margin-top: 10px; }
    
    .custom-timeline { padding-left: 5px; }
    .timeline-item { position: relative; padding-left: 25px; margin-bottom: 25px; }
    .timeline-item::before { content: ''; position: absolute; left: 0; top: 6px; width: 10px; height: 10px; border-radius: 50%; background: var(--dot-color, #9ca3af); z-index: 2; }
    .timeline-item::after { content: ''; position: absolute; left: 4px; top: 16px; width: 2px; height: calc(100% + 15px); background: #e5e7eb; z-index: 1; }
    .timeline-item:last-child::after { display: none; }
    .timeline-title { font-weight: 500; color: #1f2937; margin-bottom: 2px; font-size: 0.95rem; }
    .timeline-date { color: #9ca3af; font-size: 0.8rem; }
  </style>
  <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@1/index.js"></script>
@endpush

@section('content')
<nav class="page-breadcrumb d-flex justify-content-between align-items-center">
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-lucide="home" class="icon-sm"></i></a></li>
    <li class="breadcrumb-item"><a href="{{ route('conversations.index') }}">Conversations</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $conversation->contact->phone_number ?? 'Unknown' }}</li>
  </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <div class="text-muted small">Conversation#{{ $conversation->id }}</div>
        <h4 class="mb-0">{{ $conversation->contact->custom_name ?? $conversation->contact->whatsapp_profile_name ?? 'Unknown' }}</h4>
    </div>
    <div class="d-flex align-items-center">
        <span class="bg-success rounded-circle me-2" style="width: 8px; height: 8px;"></span> <span class="text-muted small">Live</span>
    </div>
</div>

<div class="row">
    <!-- Left Column (Sections 1 & 2) -->
    <div class="col-lg-5 col-md-12">
        <!-- Section 1 -->
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body">
                <div class="section-title">SECTION 1: CONVERSATION HEADER</div>
                <div class="row mb-3">
                    <div class="col-6">
                        <small class="text-muted d-block">Conversation No.</small>
                        <strong>#{{ $conversation->id }}</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Status</small>
                        <span class="badge bg-primary-subtle text-primary rounded-pill">{{ ucfirst(strtolower($conversation->status)) }}</span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <small class="text-muted d-block">Title</small>
                        <strong>{{ $conversation->title ?? '-' }}</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Department</small>
                        <strong>-</strong>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <small class="text-muted d-block">Assigned</small>
                        <div class="d-flex align-items-center">
                            @if($conversation->assignedUser)
                                <img src="{{ url('https://ui-avatars.com/api/?name=' . urlencode($conversation->assignedUser->name) . '&background=random&rounded=true&size=20') }}" class="rounded-circle me-1"> <strong>{{ $conversation->assignedUser->name }}</strong>
                            @else
                                <span class="badge bg-light text-dark rounded-circle me-1 border">U</span> <strong>Unassigned</strong>
                            @endif
                        </div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Created</small>
                        <strong>{{ $conversation->created_at->format('M j, Y') }}</strong>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-6">
                        <small class="text-muted d-block">Last Activity</small>
                        <strong>{{ $conversation->last_message_at ? \Carbon\Carbon::parse($conversation->last_message_at)->format('M j, Y h:i A') : '-' }}</strong>
                    </div>
                </div>
                
                <div class="d-flex gap-2 border-top pt-3">
                    <button class="btn btn-sm btn-outline-secondary fw-bold">Assign</button>
                    <button class="btn btn-sm btn-outline-secondary fw-bold">Resolve</button>
                    <button class="btn btn-sm btn-outline-secondary fw-bold">Close</button>
                    <button class="btn btn-sm btn-outline-secondary fw-bold">Reopen</button>
                </div>
            </div>
        </div>

        <!-- Section 2 -->
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="section-title mb-0">SECTION 2: CUSTOMER INFORMATION</div>
                    <div class="d-flex align-items-center">
                        <span class="bg-success rounded-circle me-1" style="width: 6px; height: 6px;"></span> <small class="text-muted" style="font-size: 10px;">Live</small>
                    </div>
                </div>
                
                <div class="d-flex align-items-center mb-4 bg-light p-2 rounded">
                    @php $contactName = $conversation->contact->custom_name ?? $conversation->contact->whatsapp_profile_name ?? 'Unknown'; @endphp
                    <div class="me-3">
                        <span class="badge bg-white text-dark border rounded-circle p-2 fs-6">RP</span>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold">{{ $contactName }}</h6>
                        <small class="text-muted">Since {{ $conversation->contact->created_at }}</small>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-6">
                        <small class="text-muted d-block">Name</small>
                        <strong>{{ $contactName }}</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">WhatsApp</small>
                        <a href="#" class="text-primary fw-bold">{{ $conversation->contact->whatsapp_profile_name ?? $contactName }}</a>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <small class="text-muted d-block">Phone</small>
                        <strong>{{ $conversation->contact->phone_number }}</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Email</small>
                        <strong>{{ $conversation->contact->email ?? 'rushil.panchal@exam...' }}</strong>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <small class="text-muted d-block mb-1">Tags</small>
                        <div class="d-flex flex-wrap gap-1">
                            <span class="badge" style="background: #f3e5f5; color: #9c27b0;">New</span>
                            <span class="badge" style="background: #e0f7fa; color: #00acc1;">Returning</span>
                            <span class="badge bg-danger-subtle text-danger">Priority</span>
                            <span class="badge bg-primary-subtle text-primary">Support</span>
                            <span class="badge bg-secondary-subtle text-secondary">CUSTOMETAG</span>
                        </div>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-12">
                        <small class="text-muted d-block">Customer Since</small>
                        <strong>{{ $conversation->contact->created_at }}</strong>
                    </div>
                </div>
                
                <div class="d-flex gap-2 border-top pt-3">
                    <button data-bs-toggle="modal" data-bs-target="#editContactModal" class="btn btn-sm btn-outline-secondary fw-bold"><i data-lucide="user" class="icon-sm me-1"></i> Edit Contact</button>
                    <button data-bs-toggle="modal" data-bs-target="#addTagModal" class="btn btn-sm btn-outline-secondary fw-bold"><i data-lucide="tag" class="icon-sm me-1"></i> Add Tag</button>
                    <button data-bs-toggle="modal" data-bs-target="#viewContactsModal" class="btn btn-sm btn-outline-secondary fw-bold"><i data-lucide="eye" class="icon-sm me-1"></i> View Contact</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column (Section 3) -->
    <div class="col-lg-7 col-md-12">
        <div class="card h-100 d-flex flex-column shadow-sm border-0">
            <div class="card-body d-flex flex-column p-0">
                <div class="p-4 border-bottom">
                    <div class="section-title mb-0">SECTION 3: CONVERSATION TIMELINE</div>
                </div>
                
                <div class="chat-timeline flex-grow-1 p-4">
                    <div class="text-center mb-4">
                        <span class="bg-success rounded-circle d-inline-block me-1" style="width: 8px; height: 8px;"></span> <span class="text-muted small fw-bold">Connected</span>
                    </div>

                    @php
                        $messages = \App\Models\Message::where('conversation_id', $conversation->id)->orderBy('sent_at', 'asc')->get();
                    @endphp
                    
                    @forelse($messages as $msg)
                        <div class="chat-bubble {{ $msg->direction === 'INBOUND' ? 'chat-inbound' : 'chat-outbound' }}">
                            {{ $msg->message_text }}
                            <div class="chat-time">{{ \Carbon\Carbon::parse($msg->sent_at)->format('h:i A') }}</div>
                        </div>
                    @empty
                        <!-- Dummy data to match screenshot -->
                        <div class="chat-bubble chat-outbound">
                            Thanks for your message! I can help with product info & pricing, booking a demo, or order support. You can also reach our team directly here: https://trpgps.com/contact-us/
                            <div class="chat-time">08:29 AM</div>
                        </div>
                        <div class="chat-bubble chat-inbound">
                            faq document
                            <div class="chat-time">08:29 AM</div>
                        </div>
                        <div class="chat-bubble chat-outbound">
                            Thanks for your message! I can help with product info & pricing, booking a demo, or order support. You can also reach our team directly here: https://trpgps.com/contact-us/
                            <div class="chat-time">08:29 AM</div>
                        </div>
                        <div class="chat-bubble chat-inbound">
                            wiring diagram
                            <div class="chat-time">08:29 AM</div>
                        </div>
                        <div class="chat-bubble chat-outbound">
                            Here you go: https://whatsapp.trpgps.com/faq/files/95de1bc5/wiring-diagram.pdf
                            <div class="chat-time">08:29 AM</div>
                        </div>
                    @endforelse
                    <div style="clear:both;"></div>
                </div>
                
                <div class="p-3 border-top bg-white position-relative">
                    
                    <!-- File Previews Container -->
                    <div id="filePreviewContainer" class="d-flex flex-wrap gap-2 mb-2 d-none p-2 rounded" style="background: #f8f9fa; border: 1px dashed #ced4da;"></div>

                    <div class="chat-input-area bg-light position-relative">
                        <!-- Emoji Picker Container -->
                        <div id="emojiPickerContainer" class="d-none position-absolute shadow rounded" style="bottom: 110%; left: 0; z-index: 1000;">
                            <emoji-picker></emoji-picker>
                        </div>
                        
                        <i data-lucide="smile" class="text-muted mx-2 cursor-pointer icon-sm" id="btnEmoji"></i>
                        
                        <!-- Attachment Dropdown -->
                        <div class="dropdown dropup">
                            <div class="cursor-pointer dropdown-toggle d-flex align-items-center justify-content-center" data-bs-toggle="dropdown" aria-expanded="false" style="background: #e3f2fd; width: 28px; height: 28px; border-radius: 50%; margin: 0 5px;">
                                <i data-lucide="paperclip" class="text-primary" style="width: 14px; height: 14px;"></i>
                            </div>
                            <ul class="dropdown-menu mb-2 shadow border-0 rounded-3 p-2" style="min-width: 200px;">
                                <li><a class="dropdown-item py-2 rounded d-flex align-items-center" href="#" id="btnAttachImageVideo"><i data-lucide="image" class="icon-sm text-success me-3"></i> Image / Video</a></li>
                                <li><a class="dropdown-item py-2 rounded d-flex align-items-center" href="#" id="btnAttachDocument"><i data-lucide="file-text" class="icon-sm text-primary me-3"></i> Document</a></li>
                            </ul>
                        </div>

                        <input type="file" id="chatAttachmentInput" class="d-none" multiple>

                        <input type="text" id="chatMessageInput" class="chat-input px-2" placeholder="Type a message...">
                        <button id="chatSendBtn" class="btn btn-success rounded-circle p-0 ms-2 d-flex align-items-center justify-content-center border-0" style="width: 35px; height: 35px; background-color: #00c853;">
                            <i data-lucide="mic" id="chatSendIcon" class="text-white" style="width: 16px; height: 16px;"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Section 5: Stats -->
<div class="card mt-4 mb-4 shadow-sm border-0">
    <div class="card-body">
        <div class="section-title mb-1">SECTION 5: CONVERSATION STATISTICS</div>
        <p class="text-muted small mb-4">Breakdown of messages and media sharing statistics</p>
        
        @php
            $msgStats = \App\Models\Message::where('conversation_id', $conversation->id)->get();
            $total = $msgStats->count() ?: 45; 
            $customer = $msgStats->where('direction', 'INBOUND')->count() ?: 20;
            $employee = $msgStats->where('sender_type', 'EMPLOYEE')->count() ?: 15;
            $chatbot = $msgStats->where('sender_type', 'SYSTEM')->count() ?: 10;
        @endphp

        <div class="row g-3">
            <div class="col">
                <div class="stat-card border-0" style="background: #f8f9fa;">
                    <div class="text-muted small fw-bold">TOTAL</div>
                    <div class="stat-value text-dark">{{ $total }}</div>
                </div>
            </div>
            <div class="col">
                <div class="stat-card border-0" style="background: #e3f2fd;">
                    <div class="text-muted small fw-bold">CUSTOMER</div>
                    <div class="stat-value text-primary">{{ $customer }}</div>
                </div>
            </div>
            <div class="col">
                <div class="stat-card border-0" style="background: #e8f5e9;">
                    <div class="text-muted small fw-bold">EMPLOYEE</div>
                    <div class="stat-value text-success">{{ $employee }}</div>
                </div>
            </div>
            <div class="col">
                <div class="stat-card border-0" style="background: #fff3e0;">
                    <div class="text-muted small fw-bold">CHATBOT</div>
                    <div class="stat-value text-warning">{{ $chatbot }}</div>
                </div>
            </div>
            <div class="col">
                <div class="stat-card bg-white">
                    <div class="text-muted small fw-bold">ATTACHMENTS</div>
                    <div class="stat-value text-dark">9</div>
                </div>
            </div>
            <div class="col">
                <div class="stat-card border-0" style="background: #f3e5f5;">
                    <div class="text-muted small fw-bold">IMAGES</div>
                    <div class="stat-value" style="color: #9c27b0;">2</div>
                </div>
            </div>
            <div class="col">
                <div class="stat-card border-0" style="background: #fff8e1;">
                    <div class="text-muted small fw-bold">DOCUMENTS</div>
                    <div class="stat-value" style="color: #ffb300;">3</div>
                </div>
            </div>
            <div class="col">
                <div class="stat-card border-0" style="background: #ffebee;">
                    <div class="text-muted small fw-bold">VIDEOS</div>
                    <div class="stat-value text-danger">2</div>
                </div>
            </div>
            <div class="col">
                <div class="stat-card border-0" style="background: #fce4ec;">
                    <div class="text-muted small fw-bold">AUDIO</div>
                    <div class="stat-value" style="color: #e91e63;">2</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Section 4 -->
    <div class="col-lg-8 col-md-12 mb-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="section-title mb-1">SECTION 4: FILES SHARED</div>
                <p class="text-muted small mb-4">Employee can Preview & Download any shared file</p>
                
                <ul class="nav nav-tabs nav-tabs-line mb-3" id="filesTab" role="tablist">
                  <li class="nav-item">
                    <a class="nav-link active d-flex align-items-center fw-bold text-dark" data-bs-toggle="tab" href="#images" role="tab"><i data-lucide="image" class="icon-sm me-2"></i> Images <span class="badge bg-light text-dark border ms-2">0</span></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link d-flex align-items-center text-muted" data-bs-toggle="tab" href="#docs" role="tab"><i data-lucide="file-text" class="icon-sm me-2"></i> Documents <span class="badge bg-light text-dark border ms-2">0</span></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link d-flex align-items-center text-muted" data-bs-toggle="tab" href="#videos" role="tab"><i data-lucide="video" class="icon-sm me-2"></i> Videos <span class="badge bg-light text-dark border ms-2">0</span></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link d-flex align-items-center text-muted" data-bs-toggle="tab" href="#audio" role="tab"><i data-lucide="music" class="icon-sm me-2"></i> Audio <span class="badge bg-light text-dark border ms-2">0</span></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link d-flex align-items-center text-muted" data-bs-toggle="tab" href="#links" role="tab"><i data-lucide="link" class="icon-sm me-2"></i> Links <span class="badge bg-light text-dark border ms-2">0</span></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link d-flex align-items-center text-muted" data-bs-toggle="tab" href="#others" role="tab"><i data-lucide="paperclip" class="icon-sm me-2"></i> Others <span class="badge bg-light text-dark border ms-2">0</span></a>
                  </li>
                </ul>
                <div class="tab-content border-0 p-0 text-center py-5">
                    <div class="text-muted mt-4">No files in this category</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Section 6 -->
    <div class="col-lg-4 col-md-12 mb-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="section-title mb-1">SECTION 6: CONVERSATION HISTORY</div>
                <p class="text-muted small mb-4">Timeline of events and actions performed in this conversation</p>
                
                <div class="custom-timeline mt-4">
                    <div class="timeline-item" style="--dot-color: #9ca3af;">
                        <div class="timeline-title">Conversation Started</div>
                        <div class="timeline-date">{{ $conversation->created_at->format('M j, Y h:i A') }}</div>
                    </div>
                    <div class="timeline-item" style="--dot-color: #3b82f6;">
                        <div class="timeline-title">Assigned to Rahul</div>
                        <div class="timeline-date">{{ $conversation->created_at->addMinutes(1)->format('M j, Y h:i A') }}</div>
                    </div>
                    <div class="timeline-item" style="--dot-color: #3b82f6;">
                        <div class="timeline-title">Tag Added</div>
                        <div class="timeline-date">{{ $conversation->created_at->addMinutes(2)->format('M j, Y h:i A') }}</div>
                    </div>
                    <div class="timeline-item" style="--dot-color: #f59e0b;">
                        <div class="timeline-title">Employee Changed</div>
                        <div class="timeline-date">{{ $conversation->created_at->addMinutes(15)->format('M j, Y h:i A') }}</div>
                    </div>
                    <div class="timeline-item" style="--dot-color: #10b981;">
                        <div class="timeline-title">Resolved</div>
                        <div class="timeline-date">{{ $conversation->created_at->addMinutes(45)->format('M j, Y h:i A') }}</div>
                    </div>
                    <div class="timeline-item" style="--dot-color: #ef4444;">
                        <div class="timeline-title">Reopened</div>
                        <div class="timeline-date">{{ $conversation->created_at->addMinutes(60)->format('M j, Y h:i A') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Section 7 -->
    <div class="col-lg-4 col-md-12 mb-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="section-title mb-1">SECTION 7: INTERNAL ACTIVITY</div>
                <p class="text-muted small mb-4">Useful for audit.</p>
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted small">Viewed By</span>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-light text-dark rounded-circle me-1 border p-1" style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;">R</span> <span class="fw-bold small me-1">Rahul</span> <span class="text-muted" style="font-size: 10px;">(Aug 4, 09:35 AM)</span>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted small">Assigned By</span>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-light text-dark rounded me-1 border p-1" style="font-size: 8px;">SYS</span> <span class="fw-bold small me-1">System</span> <span class="text-muted" style="font-size: 10px;">(Aug 4, 09:31 AM)</span>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted small">Resolved By</span>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-light text-dark rounded-circle me-1 border p-1" style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;">R</span> <span class="fw-bold small me-1">Rahul</span> <span class="text-muted" style="font-size: 10px;">(Aug 4, 10:15 AM)</span>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Closed By</span>
                    <strong>-</strong>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Section 8 -->
    <div class="col-lg-4 col-md-12 mb-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column">
                <div class="section-title mb-1">SECTION 8: NOTES</div>
                <p class="text-muted small mb-4">Only employees can see.</p>
                
                <div class="bg-light p-3 rounded mb-3 border">
                    <div class="fw-bold mb-1 small text-dark">System</div>
                    <div class="small">WhatsApp profile verified automatically.</div>
                    <div class="text-muted mt-2" style="font-size: 10px;">Aug 4, 2026</div>
                </div>
                
                <div class="input-group mt-auto">
                    <input type="text" class="form-control" placeholder="Type an internal note...">
                    <button class="btn btn-dark" type="button">Post</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Section 9 -->
    <div class="col-lg-4 col-md-12 mb-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="section-title mb-1">SECTION 9: CUSTOMER HISTORY</div>
                <p class="text-muted small mb-4">Previous Conversations</p>
                
                <div class="row text-center mb-4 bg-light mx-0 py-3 rounded border">
                    <div class="col-6 border-end">
                        <div class="text-muted small fw-bold" style="font-size:10px;">CONVERSATION COUNT</div>
                        <h4 class="mt-2 mb-0 fw-bold text-dark">5</h4>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small fw-bold" style="font-size:10px;">LAST CONVERSATION</div>
                        <h4 class="mt-2 mb-0 fw-bold text-dark">#1024</h4>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="fw-bold small">#1024 - Order Delay</div>
                        <div class="text-muted" style="font-size:11px;">Resolved &bull; Aug 2, 2026</div>
                    </div>
                    <span class="badge bg-success-subtle text-success rounded-pill px-2">Resolved</span>
                </div>
                <hr class="text-muted opacity-25">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold small">#1011 - Size Chart Enquiry</div>
                        <div class="text-muted" style="font-size:11px;">Closed &bull; Jul 15, 2026</div>
                    </div>
                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2">Closed</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Contact Modal -->
<div class="modal fade" id="editContactModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('contacts.update', $conversation->contact->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Edit Contact</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label text-muted small fw-bold">Customer Name</label>
            <input type="text" name="custom_name" class="form-control" value="{{ $contactName }}">
          </div>
          <div class="mb-3">
            <label class="form-label text-muted small fw-bold">WhatsApp Profile Name</label>
            <input type="text" name="whatsapp_profile_name" class="form-control" value="{{ $conversation->contact->whatsapp_profile_name }}">
          </div>
          <div class="mb-3">
            <label class="form-label text-muted small fw-bold">Phone Number</label>
            <input type="text" name="phone_number" class="form-control" value="{{ $conversation->contact->phone_number }}">
          </div>
          <div class="mb-3">
            <label class="form-label text-muted small fw-bold">Email</label>
            <input type="email" name="email" class="form-control" value="{{ $conversation->contact->email }}">
          </div>
          <div class="mb-3">
            <label class="form-label text-muted small fw-bold">Customer Since</label>
            <input type="text" class="form-control" value="{{ $conversation->contact->created_at }}" readonly>
          </div>
        </div>
        <div class="modal-footer justify-content-center">
          <button type="button" class="btn btn-outline-dark px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Add/Remove Tags Modal -->
<div class="modal fade" id="addTagModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Add / Remove Tags</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('contacts.updateTags', $conversation->contact->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label text-muted small fw-bold">Selected Tags</label>
            <div class="border rounded p-2 d-flex flex-wrap gap-1" id="selectedTagsContainer" style="min-height: 45px;">
                @forelse($conversation->contact->tags ?? [] as $tag)
                    <span class="badge bg-primary-subtle text-primary border selected-tag-badge cursor-pointer" data-id="{{ $tag->id }}">{{ $tag->tag_name }} &times;</span>
                    <input type="hidden" name="tags[]" class="tag-input-{{ $tag->id }}" value="{{ $tag->id }}">
                @empty
                    <span class="text-muted small no-tags-msg">No tags selected</span>
                @endforelse
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label text-muted small fw-bold">Available Tags</label>
            <div class="d-flex flex-wrap gap-2">
                @foreach($allTags as $tag)
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill bg-light text-dark border available-tag-btn" data-id="{{ $tag->id }}" data-name="{{ $tag->tag_name }}">+ {{ $tag->tag_name }}</button>
                @endforeach
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label text-muted small fw-bold">Add Custom Tag</label>
            <div class="input-group">
                <input type="text" id="customTagInput" class="form-control" placeholder="Enter tag name...">
                <button class="btn btn-outline-dark px-3" type="button" id="addCustomTagBtn"><i data-lucide="plus" class="icon-sm"></i></button>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 justify-content-center pt-0 pb-4">
          <button type="button" class="btn btn-outline-dark px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4">Save Tags</button>
          <button type="button" class="btn btn-primary px-4">Remove Tags</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- View Contacts Modal -->
<div class="modal fade" id="viewContactsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">All Contacts</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0 mt-3">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="border-top-0 border-bottom">ID</th>
                        <th class="border-top-0 border-bottom">Tenant<br>ID</th>
                        <th class="border-top-0 border-bottom">WhatsApp<br>Phone ID</th>
                        <th class="border-top-0 border-bottom">Phone<br>Number</th>
                        <th class="border-top-0 border-bottom">Profile<br>Name</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allContacts as $c)
                        <tr>
                            <td class="py-3">{{ $c->id }}</td>
                            <td class="py-3">{{ $c->tenant_id }}</td>
                            <td class="py-3">{{ $c->whatsapp_phone_number_id ?? '-' }}</td>
                            <td class="py-3">{{ $c->phone_number }}</td>
                            <td class="py-3">{{ $c->custom_name ?? $c->whatsapp_profile_name ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@push('custom-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function addTagToSelected(id, name) {
        // Remove 'no tags' message if exists
        const noTagsMsg = document.querySelector('.no-tags-msg');
        if (noTagsMsg) noTagsMsg.remove();
        
        // Check if already selected (by hidden input value)
        if (document.querySelector('input[name="tags[]"][value="' + id + '"]')) {
            return; // Already added
        }
        
        const newBadgeHTML = `
            <span class="badge bg-primary-subtle text-primary border selected-tag-badge cursor-pointer" data-id="${id}">
                ${name} &times;
            </span>
            <input type="hidden" name="tags[]" class="tag-input-${String(id).replace(/\s+/g, '-')}" value="${id}">
        `;
        document.getElementById('selectedTagsContainer').insertAdjacentHTML('beforeend', newBadgeHTML);
    }

    // Click available tag
    document.querySelectorAll('.available-tag-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            let id = this.getAttribute('data-id');
            let name = this.getAttribute('data-name');
            addTagToSelected(id, name);
        });
    });

    // Add custom tag
    const addCustomBtn = document.getElementById('addCustomTagBtn');
    const customInput = document.getElementById('customTagInput');
    
    if (addCustomBtn && customInput) {
        addCustomBtn.addEventListener('click', function() {
            let tagName = customInput.value.trim();
            if (tagName !== '') {
                addTagToSelected(tagName, tagName);
                customInput.value = '';
            }
        });

        // Allow enter key on custom tag input
        customInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addCustomBtn.click();
            }
        });
    }

    // Remove tag when clicking on the badge
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('selected-tag-badge')) {
            let id = e.target.getAttribute('data-id');
            e.target.remove();
            
            let safeId = String(id).replace(/\s+/g, '-');
            let hiddenInput = document.querySelector('.tag-input-' + safeId);
            if (hiddenInput) hiddenInput.remove();
            
            const container = document.getElementById('selectedTagsContainer');
            if (container && container.querySelectorAll('.selected-tag-badge').length === 0) {
                container.innerHTML = '<span class="text-muted small no-tags-msg">No tags selected</span>';
            }
        }
    });

    // Chat Logic
    const chatInput = document.getElementById('chatMessageInput');
    const chatSendIcon = document.getElementById('chatSendIcon');
    const chatSendBtn = document.getElementById('chatSendBtn');
    const chatTimeline = document.querySelector('.chat-timeline');

    // Emoji Logic
    const btnEmoji = document.getElementById('btnEmoji');
    const emojiContainer = document.getElementById('emojiPickerContainer');
    const emojiPicker = document.querySelector('emoji-picker');

    if (btnEmoji && emojiContainer && emojiPicker) {
        btnEmoji.addEventListener('click', (e) => {
            emojiContainer.classList.toggle('d-none');
        });

        emojiPicker.addEventListener('emoji-click', event => {
            chatInput.value += event.detail.unicode;
            chatInput.dispatchEvent(new Event('input'));
        });

        document.addEventListener('click', (e) => {
            if (!emojiContainer.contains(e.target) && e.target !== btnEmoji && !btnEmoji.contains(e.target)) {
                emojiContainer.classList.add('d-none');
            }
        });
    }

    // Attachment Logic
    const attachmentInput = document.getElementById('chatAttachmentInput');
    const btnImageVideo = document.getElementById('btnAttachImageVideo');
    const btnDocument = document.getElementById('btnAttachDocument');
    const filePreviewContainer = document.getElementById('filePreviewContainer');
    let selectedFiles = [];

    if (btnImageVideo && btnDocument && attachmentInput) {
        btnImageVideo.addEventListener('click', (e) => {
            e.preventDefault();
            attachmentInput.setAttribute('accept', 'image/*,video/*');
            attachmentInput.click();
        });

        btnDocument.addEventListener('click', (e) => {
            e.preventDefault();
            attachmentInput.setAttribute('accept', '.pdf,.doc,.docx,.xls,.xlsx,.txt');
            attachmentInput.click();
        });

        attachmentInput.addEventListener('change', function() {
            for(let i = 0; i < this.files.length; i++) {
                selectedFiles.push(this.files[i]);
            }
            this.value = ''; // reset input
            renderFilePreviews();
            toggleSendIcon();
        });
    }

    function renderFilePreviews() {
        if (!filePreviewContainer) return;
        
        if (selectedFiles.length === 0) {
            filePreviewContainer.classList.add('d-none');
            filePreviewContainer.innerHTML = '';
            if (chatInput && chatInput.value.trim().length === 0) {
                toggleSendIcon();
            }
            return;
        }
        
        filePreviewContainer.classList.remove('d-none');
        filePreviewContainer.innerHTML = '';
        
        selectedFiles.forEach((file, index) => {
            let previewHTML = '';
            if (file.type.startsWith('image/')) {
                const url = URL.createObjectURL(file);
                previewHTML = `<img src="${url}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #ccc;">`;
            } else {
                previewHTML = `<div class="bg-white border rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i data-lucide="file" class="icon-sm text-muted"></i></div>`;
            }
            
            const fileDiv = document.createElement('div');
            fileDiv.className = 'position-relative';
            fileDiv.innerHTML = `
                ${previewHTML}
                <button type="button" class="btn btn-sm btn-danger rounded-circle position-absolute d-flex align-items-center justify-content-center p-0" style="top: -5px; right: -5px; width: 16px; height: 16px; font-size: 10px; line-height: 1;" onclick="removeSelectedFile(${index})">&times;</button>
            `;
            filePreviewContainer.appendChild(fileDiv);
        });
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    window.removeSelectedFile = function(index) {
        selectedFiles.splice(index, 1);
        renderFilePreviews();
    }

    if (chatTimeline) {
        chatTimeline.scrollTop = chatTimeline.scrollHeight;
    }

    function toggleSendIcon() {
        if (!chatSendBtn) return;
        const hasText = chatInput.value.trim().length > 0;
        const hasFiles = selectedFiles.length > 0;
        
        if (hasText || hasFiles) {
            chatSendBtn.innerHTML = '<i data-lucide="send" class="text-white" style="width: 16px; height: 16px;"></i>';
        } else {
            chatSendBtn.innerHTML = '<i data-lucide="mic" class="text-white" style="width: 16px; height: 16px;"></i>';
        }
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    if (chatInput && chatSendBtn) {
        chatInput.addEventListener('input', toggleSendIcon);

        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                chatSendBtn.click();
            }
        });

        chatSendBtn.addEventListener('click', function() {
            const text = chatInput.value.trim();
            if (!text && selectedFiles.length === 0) return;

            // UI lock
            chatInput.disabled = true;

            const formData = new FormData();
            if (text) formData.append('message_text', text);
            selectedFiles.forEach(file => {
                formData.append('files[]', file);
            });

            fetch('{{ route("conversations.messages.store", $conversation->id) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                chatInput.disabled = false;
                chatInput.focus();
                
                if (data.success) {
                    chatInput.value = '';
                    selectedFiles = [];
                    renderFilePreviews();
                    toggleSendIcon();
                    
                    if (data.messages && data.messages.length > 0) {
                        data.messages.forEach(msg => {
                            appendMessageToChat(msg.message_text, 'OUTBOUND', msg.sent_at);
                        });
                    } else if (data.message) {
                        // fallback for previous structure
                        appendMessageToChat(data.message.message_text, 'OUTBOUND', data.message.sent_at);
                    }
                } else {
                    alert('Error: ' + (data.error || 'Could not send'));
                }
            })
            .catch(err => {
                console.error(err);
                chatInput.disabled = false;
                alert('Failed to send message.');
            });
        });
    }

    function appendMessageToChat(text, direction, timeStr) {
        const isOutbound = direction === 'OUTBOUND';
        const time = new Date(timeStr);
        const timeFormatted = time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        const bubble = document.createElement('div');
        bubble.className = `chat-bubble ${isOutbound ? 'chat-outbound' : 'chat-inbound'}`;
        
        // Escape HTML
        const safeText = text.replace(/</g, "&lt;").replace(/>/g, "&gt;");
        
        bubble.innerHTML = `
            ${safeText}
            <div class="chat-time">${timeFormatted}</div>
        `;

        const clearDiv = chatTimeline.querySelector('div[style="clear:both;"]');
        if (clearDiv) {
            chatTimeline.insertBefore(bubble, clearDiv);
        } else {
            chatTimeline.appendChild(bubble);
            chatTimeline.insertAdjacentHTML('beforeend', '<div style="clear:both;"></div>');
        }
        
        chatTimeline.scrollTop = chatTimeline.scrollHeight;
    }

    // WebSocket Integration
    if (typeof Echo !== 'undefined') {
        Echo.channel('conversation.{{ $conversation->id }}')
            .listen('NewMessage', (e) => {
                if (e.message && e.message.direction === 'INBOUND') {
                    appendMessageToChat(e.message.message_text, e.message.direction, e.message.sent_at);
                }
            });
    }
});
</script>
@endpush
