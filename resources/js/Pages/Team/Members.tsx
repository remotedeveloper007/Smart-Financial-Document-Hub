import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent, useEffect, useState } from 'react';
import { csrfFetch } from '@/lib/csrfFetch';

type Role = {
    id: number;
    name: string;
    slug: string;
};

type Member = {
    id: number;
    name: string;
    email: string;
    roles: Role[];
};

export default function Members() {
    const [members, setMembers] = useState<Member[]>([]);
    const [roles, setRoles] = useState<Role[]>([]);
    const [selectedRole, setSelectedRole] = useState('');
    const [selectedUserId, setSelectedUserId] = useState<number | null>(null);

    const memberForm = useForm({
        name: '',
        email: '',
        role_slug: '',
    });

    const roleForm = useForm({
        role_slug: '',
    });

    useEffect(() => {
        loadMembers();
    }, []);

    const loadMembers = async () => {
        const response = await fetch(route('team.members.index')); // eslint-disable-line no-undef
        const payload = await response.json();

        setMembers(payload.members);
        setRoles(payload.roles);
    };

    const createMember = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        await memberForm.post(route('team.members.store'));

        memberForm.reset();
        loadMembers();
    };

    const updateRole = async () => {
        if (selectedUserId === null) {
            return;
        }

        await roleForm.put(route('team.members.update', selectedUserId));

        setSelectedUserId(null);
        loadMembers();
    };

    const removeMember = async (userId: number) => {
        // The previous direct DELETE request depended on a manually-read token and could fail when the meta tag was absent.
        await csrfFetch(route('team.members.destroy', userId), {
            method: 'delete',
            headers: {
                'Accept': 'application/json',
            },
        });

        loadMembers();
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Team Members
                </h2>
            }
        >
            <Head title="Team Members" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                    <div className="bg-white p-6 shadow sm:rounded-lg">
                        <h3 className="text-lg font-semibold">Invite member</h3>
                        <form onSubmit={createMember} className="mt-4 space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Name
                                </label>
                                <input
                                    type="text"
                                    value={memberForm.data.name}
                                    onChange={(event) =>
                                        memberForm.setData(
                                            'name',
                                            event.target.value,
                                        )
                                    }
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Email
                                </label>
                                <input
                                    type="email"
                                    value={memberForm.data.email}
                                    onChange={(event) =>
                                        memberForm.setData(
                                            'email',
                                            event.target.value,
                                        )
                                    }
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Role
                                </label>
                                <select
                                    value={memberForm.data.role_slug}
                                    onChange={(event) =>
                                        memberForm.setData(
                                            'role_slug',
                                            event.target.value,
                                        )
                                    }
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                >
                                    <option value="">Select role</option>
                                    {roles.map((role) => (
                                        <option key={role.id} value={role.slug}>
                                            {role.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <button
                                type="submit"
                                className="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                            >
                                Invite member
                            </button>
                        </form>
                    </div>

                    <div className="bg-white p-6 shadow sm:rounded-lg">
                        <h3 className="text-lg font-semibold">Team members</h3>
                        <div className="mt-4 space-y-4">
                            {members.map((member) => (
                                <div
                                    key={member.id}
                                    className="rounded-lg border border-gray-200 p-4"
                                >
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <div className="font-semibold">
                                                {member.name}
                                            </div>
                                            <div className="text-sm text-gray-500">
                                                {member.email}
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => removeMember(member.id)}
                                            className="rounded-md bg-red-600 px-3 py-1 text-sm font-semibold text-white hover:bg-red-500"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                    <div className="mt-3 flex items-center gap-4">
                                        <select
                                            value={
                                                selectedUserId === member.id
                                                    ? selectedRole
                                                    : member.roles[0]?.slug ?? ''
                                            }
                                            onChange={(event) => {
                                                setSelectedUserId(member.id);
                                                setSelectedRole(event.target.value);
                                                roleForm.setData(
                                                    'role_slug',
                                                    event.target.value,
                                                );
                                            }}
                                            className="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        >
                                            {roles.map((role) => (
                                                <option key={role.id} value={role.slug}>
                                                    {role.name}
                                                </option>
                                            ))}
                                        </select>
                                        <button
                                            type="button"
                                            onClick={updateRole}
                                            className="inline-flex items-center rounded-md bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-600"
                                        >
                                            Update role
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
